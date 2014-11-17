# dployer
=======

A simple script to deploy PHP applications in a few minutes to ElasticBeanstalk.

## Installation

### Composer

```shell
$ composer global require "leroy-merlin-br/dployer=*@dev"
```

## Configuration

### AWS config

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
``` shell
export DPLOYER_BUCKET=your-bucket-identifier-0-12345678
```

## Usage
Inside the folder that you want to deploy, just run:
```shell
$ dployer deploy ApplicationName elasticbeanstalked-environment
