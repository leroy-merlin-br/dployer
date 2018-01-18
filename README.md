# dployer

A simple script to deploy PHP applications in a few minutes to ElasticBeanstalk.

---

- [Installation](#installation)
  - [Composer](#composer)
- [Global config for dployer](#global-config-for-dployer)
  - [AWS Config](#aws-config)
    - [Environment Variables](#environment-variables)
    - [JSON configuration file](#json-configuration-file)
  - [AWS Bucket](#aws-bucket)
- [Usage](#usage)
  - [Options](#options)
- [Project Configuration](#project-configuration)
  - [Sample](#sample-dployer)
  - [Events](#events)
  - [copy-paths](#copy-paths)
  - [exclude-paths](#exclude-paths)

---

## Installation

### Composer

```shell
composer global require "leroy-merlin-br/dployer=*@dev"
```

## Global config for dployer

### AWS config

You have 2 options to configure AWS:

- Environment Variables
- JSON configuration file

#### Environment Variables

You must fill the following environment variables.

- `DPLOYER_PROFILE` : Your profile's name in AWS.
- `DPLOYER_REGION`  : Your region you want to deploy something.
- `DPLOYER_AWS_KEY` : Your secret AWS key.
- `DPLOYER_AWS_SECRET` : Your secret AWS SECRET.

#### JSON Configuration File

- Create the following configuration file: `~/.aws/config.json`

```json
{
    "includes": ["_aws"],
    "services": {
        "default_settings": {
            "params": {
                "profile": "my_profile",
                "region": "sa-east-1",
                "key": "YOURSUPERKEY",
                "secret": "YoUrSuPeRsEcReT"
            }
        }
    }
}
```

### AWS Bucket

Add the following line in the end of the `~/.bashrc` file:

```shell
export DPLOYER_BUCKET=your-bucket-identifier-0-12345678
```

## Usage

Inside the folder that you want to deploy, just run:

```shell
dployer deploy ApplicationName elasticbeanstalked-environment
```

### Options

You can use the following options:

- **-c (--config)**: Use a custom configuration file different from .dployer
- **-i (--interactive)**: Asks before run each command in configuration file
- **-v (--verbose)**: Display command outputs
- **-f (--force)**: Continue with deploy process even if a script exits with error

## Project configuration

In order to optimize the deploy of your project, you can create a configuration
file to keep application and environment variables. In addition, you gain some
extra features, like: events to run the scripts that you want and options to
copy extra files and delete some files before zip them.

Just create a `.dployer` file in project root dir.

**Note:** Once you have `.dployer` file with application and environment
variables defined, you can just run the command as following:

```shell
dployer deploy
```

### Sample `.dployer`

```json
{
    "application": "ApplicationName",
    "environment": "my-environment",
    "scripts": {
        "init": "composer dumpautoload",
        "before-pack": [
            "gulp build --production"
        ],
        "before-deploy": [
            "echo 'Deploying new version'",
            "echo 'Another important command to run before deploy'"
        ],
        "finish": [
            "gulp clean",
            "echo 'Nicely done'"
        ]
    },
    "copy-paths": [
        "vendor",
        "public/assets"
    ],
    "exclude-paths": [
        ".git",
        "vendor/**/*.git"
    ]
}
```
### Events

Dployer triggers 4 events in deploy flow:

- **init**: Runs after initial validations and before any command of deploy
- **before-pack**: Runs before create the zip file
- **before-deploy**: Runs before create ElasticBeanstalk version and upload zip
- **finish**: Runs after upload new version

### copy-paths

The dployer just clone your current git branch inside a temp folder, then it
creates a zip file. But sometimes, you want to deploy some files which are
ignored by git (inside `.gitignore` file).

In this case, you can put these files/folders in `copy-paths` key in
configuration file as demonstrated in sample section.

`.dployer`

```json
(...)
"copy-paths": [
    "vendor",
    "public/assets/"
]
(...)
```

### exclude-paths

In another case, sometimes you want to exclude some files/folders too.

`.dployer`

```json
(...)
"exclude-paths": [
    ".git",
    "vendor/**/*.git"
]
(...)
```
