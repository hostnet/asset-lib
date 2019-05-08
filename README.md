# hostnet/asset-lib
[![Travis Status](https://travis-ci.org/hostnet/asset-lib.svg?branch=master)](https://travis-ci.org/hostnet/asset-lib)
[![AppVeyor Status](https://ci.appveyor.com/api/projects/status/github/hostnet/asset-lib?svg=true)](https://ci.appveyor.com/project/yannickl88/asset-lib)

> This project is work in progress, nothing is final! Use at own risk.

An asset resolver inspired by webpack. This is used in conjunction with Symfony and the [asset-bundle](https://github.com/hostnet/asset-bundle).

## Assumptions:
This library makes a single assumption, the `node_modules` should always be at the root of a project (so: `./node_modules`).
