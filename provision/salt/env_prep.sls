# set up data first
###########################################################
{%- set project = pillar.get('project') %}
{%- set database = pillar.get('database') %}
{%- set magento = pillar.get('magento') %}
{%- set magento_version = magento['version'] %}
{%- set magento_extensions = pillar.get('extensions',{}) %}
{%- set web_root = "/var/app/" + saltenv + "/html/" %}
{%- set stage_root = "salt://stage/vagrant/" %}

{% set vars = {'isLocal': False} %}
{% if vars.update({'ip': salt['cmd.run']('(ifconfig eth1 2>/dev/null || ifconfig eth0 2>/dev/null) | grep "inet " | awk \'{gsub("addr:","",$2);  print $2 }\'') }) %} {% endif %}
{% if vars.update({'isLocal': salt['cmd.run']('test -n "$SERVER_TYPE" && echo $SERVER_TYPE || echo "false"') }) %} {% endif %}


pre-clear-caches:
  cmd.run:
    - name: rm -rf {{ web_root }}var/cache/* {{ web_root }}var/session/* {{ web_root }}var/report/* {{ web_root }}var/locks/* {{ web_root }}var/log/* {{ web_root }}app/code/core/Zend/Cache/* {{ web_root }}media/css/* {{ web_root }}media/js/* 
    - user: root
    - onlyif: test -d {{ web_root }}var



# Create service checks
###########################################################
mysqld-{{ saltenv }}:
  service.running:
    - name: mysqld

php-{{ saltenv }}:
  service.running:
    - name: php-fpm

nginx-{{ saltenv }}:
  service.running:
    - name: nginx


# Stop services
###########################################################
{% if 'webcaching' in grains.get('roles') %}
# Turn off all caches
memcached-stopped:
  cmd.run:
    - name: service memcached stop
    - cwd: /
{% endif %}


# Setup the MySQL requirements for WSUMAGE-base
###########################################################
magedb-{{ database['name'] }}:
  mysql_database.present:
    - name: {{ database['name'] }}
    - require:
      - service: mysqld-{{ saltenv }}

magedb_users-{{ database['user'] }}:
  mysql_user.present:
    - name: {{ database['user'] }}
    - password: {{ database['pass'] }}
    - host: {{ database['host'] }}
    - require:
      - service: mysqld-{{ saltenv }}
      
magedb_grant-{{ database['name'] }}:
  mysql_grants.present:
    - grant: all
    - host: {{ database['host'] }}
    - database: {{ database['name'] }}.*
    - user: {{ database['user'] }}
    - require:
      - service: mysqld-{{ saltenv }}






{{ web_root }}:
    file.directory:
    - user: www-data
    - group: www-data
    - dir_mode: 775
    - file_mode: 664


{{ web_root }}media:
    file.directory:
    - user: www-data
    - group: www-data
{% if not vars.isLocal %}
    - dir_mode: 777
    - file_mode: 777
{%- endif %}

{{ web_root }}skin:
    file.directory:
    - user: www-data
    - group: www-data
{% if not vars.isLocal %}
    - dir_mode: 777
    - file_mode: 777
{%- endif %}

{{ web_root }}var:
    file.directory:
    - user: www-data
    - group: www-data
{% if not vars.isLocal %}
    - dir_mode: 777
    - file_mode: 777
{%- endif %}

{{ web_root }}maps:
    file.directory:
    - user: www-data
    - group: www-data
{% if not vars.isLocal %}
    - dir_mode: 775
    - file_mode: 744
{%- endif %}




#Modgit for magento modules
gitploy:
  cmd.run:
    - name: curl https://raw.github.com/jeremyBass/gitploy/master/gitploy | sudo sh -s -- install
    - cwd: /
    - user: root
    - unless: which gitploy

#start modgit tracking
init_gitploy:
  cmd.run:
    - name: gitploy init
    - cwd: {{ web_root }}
    - unless: test -d {{ web_root }}.gitploy
    - user: root




magento:
  cmd.run:
    - name: 'gitploy ls 2>&1 | grep -qi "MAGE" && gitploy up -t {{ magento['version'] }} MAGE || gitploy -q -t {{ magento['version'] }} MAGE https://github.com/washingtonstateuniversity/magento-mirror.git'
    - cwd: {{ web_root }}
    - user: root
    - require:
      - service: mysqld-{{ saltenv }}
      - service: php-{{ saltenv }}




# move the apps nginx rules to the site-enabled
/etc/nginx/mage-networked.conf:
  file.managed:
    - source: salt://config/nginx/mage-networked.conf
    - user: root
    - group: root
    - template: jinja
    - context:
      isLocal: {{ vars.isLocal }}
      saltenv: {{ saltenv }}
      web_root: {{ web_root }}
      magento: {{ magento }}

# move the apps nginx rules to the site-enabled
/etc/nginx/sites-enabled/store.network.conf:
  file.managed:
    - source: salt://config/nginx/store.network.conf
    - user: root
    - group: root
    - template: jinja
    - context:
      isLocal: {{ vars.isLocal }}
      saltenv: {{ saltenv }}
      web_root: {{ web_root }}
      magento: {{ magento }}


# move the apps nginx rules to the site-enabled
{{ web_root }}maps/nginx-mapping.conf:
  file.managed:
    - source: salt://config/nginx/maps/nginx-mapping.conf
    - user: www-data
    - group: www-data
    - makedirs: true
{% if not vars.isLocal %}
    - mode: 744
{%- endif %}
    - template: jinja
    - context:
      isLocal: {{ vars.isLocal }}
      saltenv: {{ saltenv }}
      web_root: {{ web_root }}
      magento: {{ magento }}


restart-nginx-{{ saltenv }}:
  cmd.run:
    - name: service nginx restart
    - user: root
    - cwd: /
    - require:
      - service: nginx-{{ saltenv }}

/etc/incron.d/mapping.conf:
  file.managed:
    - source: salt://config/incron/incron.d/mapping.conf
    - makedirs: true
    - user: root
    - group: root
    - template: jinja
    - context:
      isLocal: {{ vars.isLocal }}
      saltenv: {{ saltenv }}
      web_root: {{ web_root }}
      magento: {{ magento }}

/etc/incron.d/ngx_pagespeed.conf:
  file.managed:
    - source: salt://config/incron/incron.d/ngx_pagespeed.conf
    - makedirs: true
    - user: root
    - group: root
    - template: jinja
    - context:
      isLocal: {{ vars.isLocal }}
      saltenv: {{ saltenv }}
      web_root: {{ web_root }}
      magento: {{ magento }}
