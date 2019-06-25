<?php
namespace snitch\authevesso;

class AUTHEVESSO_LOG {
        
        protected $db;
        protected $table_prefix;

        function __construct()
        {
                global $db, $table_prefix;
                $this->db = $db;
                $this->table_prefix = $table_prefix;
        }

        public function add($comment = '', $user_id = null, $characterID = null, $time = null) {
                ($time == null?$time=time():'');
                $arr = array('log_time' => $time, 'user_id' => $user_id, 'characterID' => $characterID, 'comment' => $comment);
                $sql = 'INSERT INTO ' . $this->table_prefix . 'authevesso_logs ' . $this->db->sql_build_array('INSERT', $arr);
                $this->db->sql_query($sql);
        } 

}
