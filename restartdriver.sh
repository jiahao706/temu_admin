#!/bin/bash

ps -ef|grep -i chrom|grep -v 'php\|restartdriver\|grep'|awk '{print $2}'|xargs kill -9
rm -f /chromedriver.log
rm -f ./download
rm -rf ./selenium
nohup $1 --port=$2 --remote-debugging-port=9527 >>chromedriver.log 2>&1 &
