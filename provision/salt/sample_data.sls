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

## note it should be that only on need does this get run
## question what the need is, then test for it


## if the repo of sample data exists
reload-sampledata:
  cmd.run:
    - onlyif: gitploy ls 2>&1 | grep -qi "sampledata"
    - name: gitploy re -q -b 1.9.1.0 sampledata
    - cwd: {{ web_root }}
    - user: root
    - require:
      - service: mysqld-{{ saltenv }}
##else load it
load-sampledata:
  cmd.run:
    - unless: gitploy ls 2>&1 | grep -qi "sampledata"
    - name: gitploy ls 2>&1 | grep -qi "MAGE" && gitploy -q -b 1.9.1.0-clean sampledata https://github.com/washingtonstateuniversity/WSUMAGE-sampledata.git
    - cwd: {{ web_root }}
    - user: root
    - require:
      - service: mysqld-{{ saltenv }}
##end


set_mysql_config_editor-sample-date:
  cmd.run:
    - name: 'touch .mylogin.cnf && printf "[local]\nuser = {{ database['user'] }}\npassword = {{ database['pass'] }}\nhost = {{ database['host'] }}" >> .mylogin.cnf'
    - cwd: /



##install sample data
install-sample-date:
  cmd.run:
    - onlyif: test -f sample-data.sql
    - unless: count=$(mysql  --login-path=local --skip-column-names  --batch -D {{ database['name'] }} -e 'SELECT count(*) FROM admin_user;' 2>/dev/null) && test $count -gt 0
    - name: 'mysql  --login-path=local {{ database['name'] }} < sample-data.sql && mysql --login-path=local {{ database['name'] }} -e "create database somedb" && echo "export mage_sameple_data=True {% raw %}#salt-set REMOVE{% endraw %}" >> /etc/environment && mage_sameple_data=True '
    - cwd: {{ web_root }}

clear-sampledata:
  cmd.run:
    - name: rm -rf ./WSUMAGE-sampledata-master/ ./sample-data.sql ./sample-data-files/
    - user: root
    - cwd: {{ web_root }}



remove_mysql_config_editor-sample-date:
  cmd.run:
    - name: 'mysql_config_editor remove --login-path=local'
    - cwd: /
