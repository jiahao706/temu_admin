#!/bin/bash

ps -ef|grep -i chrom|grep -v 'php\|restartdriver\|grep'|awk '{print $2}'|xargs kill -9
