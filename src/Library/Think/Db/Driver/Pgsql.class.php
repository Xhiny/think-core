<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace Think\Db\Driver;
use Think\Db\Driver;

/**
 * Pgsql数据库驱动
 */
class Pgsql extends Driver{

    /**
     * 解析pdo连接的dsn信息
     * @access public
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn($config){
        $dsn  =   'pgsql:dbname='.$config['database'].';host='.$config['hostname'];
        if(!empty($config['hostport'])) {
            $dsn  .= ';port='.$config['hostport'];
        }
        return $dsn;
    }

    /**
     * 取得数据表的字段信息
     * @access public
     * @return array
     */
    public function getFields($tableName) {
        list($tableName) = explode(' ', $tableName);
        $result =   $this->query($this->compileColumns($tableName));
        $info   =   [];
        if($result){
            foreach ($result as $key => $val) {
                $val['extra'] = $val['default'];
                $autoinc = $val['default'] && preg_match('/^(nextval)/', $val['default']);
                $primary = !is_null($val['contype']);
                $notnull = $val['nullable'] === 'f';
                $info[$val['field']] = array(
                    'name'    => $val['field'],
                    'type'    => $val['type'],
                    'notnull' => $notnull, // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => $primary,
                    'autoinc' => $autoinc,
                );
            }
        }
        return $info;
    }

    protected function compileColumns($table_name)
    {
        return sprintf(
            'select a.attname as field, t.typname as type_name, format_type(a.atttypid, a.atttypmod) as type, '
            .'(select tc.collcollate from pg_catalog.pg_collation tc where tc.oid = a.attcollation) as collation, '
            .'not a.attnotnull as nullable, '
            .'(select pg_get_expr(adbin, adrelid) from pg_attrdef where c.oid = pg_attrdef.adrelid and pg_attrdef.adnum = a.attnum) as default, '
            .'col_description(c.oid, a.attnum) as comment, '
            .'const.contype '
            .'from pg_attribute a '
            .'left join pg_class c on a.attrelid = c.oid '
            .'left join pg_type t on a.atttypid = t.oid '
            .'left join pg_namespace n on n.oid = c.relnamespace '
            .'left join pg_constraint const on a.attnum= ANY(const.conkey) and a.attrelid = const.conrelid '
            .'where c.relname = %s and n.nspname = %s and a.attnum > 0 '
            .'order by a.attnum',
            "'$table_name'",
            "'public'"
        );
    }

    /**
     * 取得数据库的表信息
     * @access public
     * @return array
     */
    public function getTables($dbName='') {
        $result =   $this->query("select tablename as Tables_in_test from pg_tables where  schemaname ='public'");
        $info   =   array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * limit分析
     * @access protected
     * @param mixed $lmit
     * @return string
     */
    public function parseLimit($limit) {
        $limitStr    = '';
        if(!empty($limit)) {
            $limit  =   explode(',',$limit);
            if(count($limit)>1) {
                $limitStr .= ' LIMIT '.$limit[1].' OFFSET '.$limit[0].' ';
            }else{
                $limitStr .= ' LIMIT '.$limit[0].' ';
            }
        }
        return $limitStr;
    }

}
