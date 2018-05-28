<?php
namespace EasyCaptcha\EasyCaptcha;

class Db
{
    private $_config = [];
    private $_pdo = null;

    public function __construct($config)
    {
        $dsn        =  "mysql:dbname={$config['database']};port={$config['hostport']};host={$config['hostname']}" ;
        $user       =  $config['username'] ;
        $password   =  $config['password'] ;

        $this->_config = $config;
        $this->_pdo  = new  \PDO ( $dsn ,  $user ,  $password );
        $this->_createTable();
    }

    public function getCodeCount($condition)
    {
        $res = $this->getCodeInfo($condition, 'count(*) as c');
        if(empty($res)){
            return 0;
        }
        return $res[0]['c'];
    }

    public function getCodeInfo($condition, $fields = '*')
    {
        $where = [];
        foreach($condition as $field => $value){
            if(is_array($value)){
                $where[] = "$field {$value[0]} '{$value[1]}'";
            }else{
                $where[] = "$field = '{$value}'";
            }
        }
        $where = implode(' and ', $where);

        $sth  =  $this->_pdo->prepare("select {$fields} from {$this->_config['table']} where {$where} order by id desc limit 1");
        $sth -> execute ();
        $res  =  $sth -> fetchAll ();
        if(empty($res)){
            return [];
        }
        return $res[0];
    }

    public function deleteCode($account_type, $code)
    {
        $res = $this->_pdo->exec( "delete from {$this->_config['table']} where account_type = '{$account_type}' and code = '{$code}'" );
        if($res){
            return true;
        }
        throw new \Exception('delete code error');
    }

    //删除一天前且过期验证码
    public function deleteExpireCode()
    {
        $time = time();
        $send_time = $time-3600*24;

        $res = $this->_pdo->exec("delete from {$this->_config['table']} where send_time < {$send_time} and expire_time < {$time}");
        if(false !== $res){
            return true;
        }
        throw new \Exception('delete expire code error');
    }

    public function insertCode($data)
    {
        $values = "'{$data['account']}','{$data['account_type']}','{$data['code']}','{$data['ip']}','{$data['send_time']}','{$data['expire_time']}'";

        $sql = "INSERT INTO `{$this->_config['table']}` (`account`, `account_type`, `code`, `ip`, `send_time`, `expire_time`) VALUES ({$values})";
        $res = $this->_pdo->exec($sql);
        if($res){
            return true;
        }
        throw new \Exception('insert code error');
    }

    //创建消息发送记录表
    private function _createTable()
    {
        foreach ( $this->_pdo->query('show tables') as  $row ) {
            $table = strtolower($row['Tables_in_'.$this->_config['database']]);
            if($table == $this->_config['table']){
                return true;
            }
        }

        $sql = "
                CREATE TABLE `{$this->_config['table']}` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `account` VARCHAR(255) NOT NULL,
                    `account_type` ENUM('cellphone','email') NOT NULL,
                    `code` VARCHAR(6) NOT NULL,
                    `ip` VARCHAR(20) NOT NULL,
                    `send_time` INT(11) UNSIGNED NOT NULL,
                    `expire_time` INT(11) UNSIGNED NOT NULL,
                    PRIMARY KEY (`id`) USING BTREE,
                    UNIQUE INDEX `unique_code` (`account_type`, `code`),
                    INDEX `telephone` (`account`) USING BTREE,
                    INDEX `ip` (`ip`) USING BTREE
                )
                COMMENT='verify code record'
                COLLATE='utf8_general_ci'
                ENGINE=MyISAM
                ;
            ";

        $res  =  $this->_pdo->exec ($sql);
        if($res === false){
            throw new \Exception('fail to create table');
        }
        return true;
    }
}