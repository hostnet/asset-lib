<?php
/**
 * @copyright 2018 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Builder;

use Hostnet\Component\Resolver\Config\ConfigInterface;

/**
 * A build config contains the configuration of the build processes.
 *
 * @internal
 */
class BuildConfig implements \JsonSerializable
{
    /**
     * @var AbstractBuildStep[]
     */
    private $steps             = [];
    private $writers           = [];
    private $actions           = [];
    private $extension_mapping = [];
    private $paths;
    private $checksum;
    private $compiled = false;

    public function __construct(ConfigInterface $config)
    {
        $this->paths = [
            'root' => $config->getProjectRoot(),
            'out'  => $config->getOutputFolder(true),
        ];

        if (!$config->isDev()) {
            return;
        }

        $this->paths['cache'] = $config->getCacheDir();
    }

    /**
     * Return a unique hash for this config. If any of the steps change, this hash will change.
     */
    public function calculateHash(): string
    {
        if (null !== $this->checksum) {
            return $this->checksum;
        }

        $build_steps = serialize($this->paths);

        foreach ($this->steps as $step) {
            $build_steps .= $step->getHash();
        }

        foreach ($this->writers as $writer) {
            $build_steps .= $writer->getHash();
        }

        return $this->checksum = md5($build_steps);
    }

    /**
     * Register a build step to be included in the build config.
     *
     * @param AbstractBuildStep $step
     */
    public function registerStep(AbstractBuildStep $step): void
    {
        if ($this->compiled) {
            throw new \LogicException('Build config is already compiled and can no longer change.');
        }

        $this->steps[] = $step;
    }

    /**
     * Register a writer action to be included in the build config.
     *
     * @param AbstractWriter $writer
     */
    public function registerWriter(AbstractWriter $writer): void
    {
        if ($this->compiled) {
            throw new \LogicException('Build config is already compiled and can no longer change.');
        }

        $this->writers[] = $writer;
    }

    /**
     * Compile the build config, this will resolve any actions and calculate the needed build steps. After this the
     * build config is immutable.
     */
    public function compile(): void
    {
        if ($this->compiled) {
            throw new \LogicException('Cannot recompile already compiled build config.');
        }

        $supported_extensions = [];
        $edges                = [];
        $i                    = 0;
        foreach ($this->steps as $step) {
            $ext = $step->acceptedExtension();

            if (max($step->acceptedStates()) > $step->resultingState()) {
                throw new \RuntimeException('Cannot go back in build states for ' . \get_class($step));
            }

            foreach ($step->acceptedStates() as $start) {
                $edges[] = [$i++, $start, $ext, $step->resultingState(), $step->resultingExtension(), $step];
            }

            if (!\in_array(AbstractBuildStep::FILE_READ, $step->acceptedStates(), true)) {
                continue;
            }

            $supported_extensions[] = $ext;
        }

        $this->actions = $this->createPlans(
            $supported_extensions,
            $this->findPaths($edges, AbstractBuildStep::FILE_READ, AbstractBuildStep::FILE_READY),
            $this->findPaths($edges, AbstractBuildStep::MODULES_COLLECTED, AbstractBuildStep::MODULES_READY)
        );

        $this->compiled = true;
    }

    /**
     * Create build plans based on the possible actions for files and modules. This returns per supported extensions a
     * list of file actions and module actions in order.
     *
     * @param array $supported_extensions
     * @param array $file_action
     * @param array $module_action
     * @return array
     */
    private function createPlans(array $supported_extensions, array $file_action, array $module_action): array
    {
        $plans = [];

        foreach ($supported_extensions as $extension) {
            $file_actions        = $this->findLongestPath(
                $file_action,
                $extension,
                AbstractBuildStep::FILE_READ,
                AbstractBuildStep::FILE_READY
            );
            $resulting_extension = $file_actions[\count($file_actions) - 1][4];
            $module_actions      = [];
            $write_actions       = [];

            // Only module actions are needed if the file extensions keep the same. Else we switch extension plan.
            if ($resulting_extension === $extension) {
                $module_actions = array_column($this->findLongestPath(
                    $module_action,
                    $resulting_extension,
                    AbstractBuildStep::MODULES_COLLECTED,
                    AbstractBuildStep::MODULES_READY
                ), 5);

                $write_actions = array_values(array_filter(
                    $this->writers,
                    function (AbstractWriter $writer) use ($extension) {
                        $writer_ext = $writer->acceptedExtension();
                        return $writer_ext === '*' || $writer_ext === $extension;
                    }
                ));

                if (\count($write_actions) === 0) {
                    throw new \LogicException("No writers configured for extension \"$extension\".");
                }
            }

            $plans[$extension] = [
                'file_actions'   => array_column($file_actions, 5),
                'module_actions' => $module_actions,
                'write_actions'  => $write_actions,
            ];

            if (!empty($module_actions)) {
                $this->extension_mapping[$extension] = $module_actions[\count($module_actions) - 1]
                    ->resultingExtension();
            } else {
                $this->extension_mapping[$extension] = $file_actions[\count($file_actions) - 1][4];
            }
        }

        return $plans;
    }

    /**
     * Removes all nodes which are not reachable or can reach an ending state. This then returns a graph with only
     * paths from the starting state to the ending state.
     *
     * @param array $edges
     * @param int   $starting_state
     * @param int   $ending_state
     * @return array
     */
    private function findPaths(array $edges, int $starting_state, int $ending_state): array
    {
        // Remove all nodes which are not visitable from a starting state nodes
        $visitable_edges = array_values(array_filter($edges, function (array $edge) use ($starting_state) {
            return $edge[1] === $starting_state;
        }));
        $seen            = array_column($visitable_edges, 0);

        while (true) {
            $has_changed = false;

            for ($i = 0, $n = \count($visitable_edges); $i < $n; $i++) {
                $state = $visitable_edges[$i][3];
                $ext   = $visitable_edges[$i][4];

                foreach ($edges as $edge) {
                    if ($edge[1] !== $state || $edge[2] !== $ext || \in_array($edge[0], $seen, true)) {
                        continue;
                    }

                    $has_changed       = true;
                    $visitable_edges[] = $edge;
                    $seen[]            = $edge[0];
                }
            }

            if (!$has_changed) {
                break;
            }
        }

        // Remove all nodes which are not visitable from the ending state nodes
        $visitable_edges_r = array_values(array_filter($visitable_edges, function (array $edge) use ($ending_state) {
            return $edge[3] === $ending_state;
        }));
        $seen              = array_column($visitable_edges_r, 0);

        while (true) {
            $has_changed = false;

            for ($i = 0, $n = \count($visitable_edges_r); $i < $n; $i++) {
                $state = $visitable_edges_r[$i][1];
                $ext   = $visitable_edges_r[$i][2];

                foreach ($edges as $edge) {
                    if ($edge[3] !== $state || $edge[4] !== $ext || \in_array($edge[0], $seen, true)) {
                        continue;
                    }

                    $has_changed         = true;
                    $visitable_edges_r[] = $edge;
                    $seen[]              = $edge[0];
                }
            }

            if (!$has_changed) {
                break;
            }
        }

        return $visitable_edges_r;
    }

    /**
     * Return the longest path from the starting state to the ending state. This will make sure the most possible edges
     * are picked alone the way.
     *
     * @param array  $edges
     * @param string $extension
     * @param int    $starting_state
     * @param int    $ending_state
     * @return array
     */
    private function findLongestPath(array $edges, string $extension, int $starting_state, int $ending_state): array
    {
        $options = [];

        foreach ($edges as $edge) {
            if ($edge[1] !== $starting_state || $edge[2] !== $extension) {
                continue;
            }

            $options[] = $edge;
        }

        $plan         = $this->makeChoices($options, $edges, $ending_state);
        $seen_edges   = array_column($options, 0);
        $seen_actions = array_column($options, 5);

        if (empty($plan)) {
            return [];
        }

        while (true) {
            $has_changed = false;

            for ($i = 0, $n = \count($plan); $i < $n; $i++) {
                $state = $plan[$i][3];
                $ext   = $plan[$i][4];

                $options = [];

                foreach ($edges as $edge) {
                    if ($edge[1] !== $state
                        || $edge[2] !== $ext
                        || \in_array($edge[0], $seen_edges, true)
                        || \in_array($edge[5], $seen_actions, true)
                    ) {
                        continue;
                    }

                    $options[] = $edge;
                }

                if (\count($options) <= 0) {
                    continue;
                }

                $has_changed  = true;
                $plan         = array_merge($plan, $this->makeChoices($options, $edges, $ending_state));
                $seen_edges   = array_merge($seen_edges, array_column($options, 0));
                $seen_actions = array_merge($seen_edges, array_column($options, 5));
            }

            if (!$has_changed) {
                break;
            }
        }

        return $plan;
    }

    /**
     * Return the max length from the starting state to the ready state.
     *
     * @param array $start
     * @param array $edges
     * @param int   $ready_state
     * @param int   $c
     */
    private function getMaxLengthToEnd(array $start, array $edges, int $ready_state, int $c = 0): int
    {
        if ($start[3] === $ready_state) {
            return $c + 1;
        }

        $lengths = [0];

        foreach ($edges as $edge) {
            // Skip edges which loop to the same state, do not have the same extension or do not match this edge
            if ($edge[1] === $edge[3] || $edge[1] !== $start[3] || $edge[2] !== $start[4]) {
                continue;
            }

            $lengths[] = $this->getMaxLengthToEnd($edge, $edges, $ready_state, $c + 1);
        }

        return max($lengths);
    }

    /**
     * Make a choice which options to pick. This will prefer the shortest transitions.
     *
     * @param array $options
     * @param array $edges
     * @param int   $ready_state
     * @return array
     */
    private function makeChoices(array $options, array $edges, int $ready_state): array
    {
        $plan = [];

        if (\count($options) === 1) {
            $first = current($options);

            $plan[] = $first;
        } elseif (\count($options) > 0) {
            // Pick the one with the highest count
            $maxes = [];

            foreach ($options as $option) {
                if ($option[1] === $option[3]) {
                    $plan[] = $option;
                    continue;
                }

                $maxes[] = [$this->getMaxLengthToEnd($option, $edges, $ready_state), $option];
            }

            $max = \count($maxes) > 0 ? max(array_column($maxes, 0)) : 0;

            $selected_edges = array_column(array_filter($maxes, function (array $max_option) use ($max) {
                return $max === $max_option[0];
            }), 1);

            if (\count($selected_edges) > 1) {
                usort($selected_edges, function (array $a, array $b) {
                    return $b[5]->buildPriority() <=> $a[5]->buildPriority();
                });

                $selected_edges = [reset($selected_edges)];
            }

            $plan = array_merge($plan, $selected_edges);

            usort($plan, function (array $a, array $b) {
                $a_is_self_loop = $a[1] === $a[3];
                $b_is_self_loop = $b[1] === $b[3];

                if ($a_is_self_loop !== $b_is_self_loop) {
                    return $a_is_self_loop ? -1 : 1;
                }

                return $b[5]->buildPriority() <=> $a[5]->buildPriority();
            });
        }

        return $plan;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        if (!$this->compiled) {
            throw new \LogicException('Cannot serialize uncompiled build config.');
        }

        $build_steps = [];

        foreach ($this->actions as $ext => $steps) {
            $build_steps[$ext] = [
                array_map(function (AbstractBuildStep $step) {
                    return $step->getJsModule();
                }, $steps['file_actions']),
                array_map(function (AbstractBuildStep $step) {
                    return $step->getJsModule();
                }, $steps['module_actions']),
                array_map(function (AbstractWriter $step) {
                    return $step->getJsModule();
                }, $steps['write_actions']),
            ];
        }

        return [
            'checksum' => $this->calculateHash(),
            'mapping'  => $this->extension_mapping,
            'paths'    => array_map(function (string $path) {
                return rtrim($path, '//\\') . DIRECTORY_SEPARATOR;
            }, $this->paths),
            'build'    => $build_steps,
        ];
    }

    public function getExtensionMap(): ExtensionMap
    {
        if (!$this->compiled) {
            throw new \LogicException('Cannot get mapping for uncompiled build config.');
        }

        return new ExtensionMap($this->extension_mapping);
    }

    public function isUpToDateWith(array $json_data): bool
    {
        return $this->calculateHash() === ($json_data['checksum'] ?? '');
    }
}
