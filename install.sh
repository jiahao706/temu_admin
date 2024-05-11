#!/bin/bash

#安装docker-compose
pip install docker-compose

#安装配置文件目录
mkdir -p `pwd`/docker_common/conf `pwd`/docker_common/log/php `pwd`/docker_common/log/nginx


touch `pwd`/docker_common/log/nginx/api-temu_access.log `pwd`/docker_common/log/nginx/api-temu_error.log `pwd`/docker_common/log/php/fpm_access.log `pwd`/docker_common/log/php/fpm_error.log `pwd`/docker_common/log/php/www-slow.log


#从服务器拷贝配置文件到本地

scp -r root@remoteip:/env/docker_common/conf/php `pwd`/docker_common/conf/php
scp -r root@remoteip:/env/docker_common/conf/nginx `pwd`/docker_common/conf/nginx

#启动本地容器

docker-compose up -d

