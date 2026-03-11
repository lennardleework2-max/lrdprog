<?php

    class db_init{

        // //FOR DB CONN ON APPSYSTEM DB containing syspar or the program file itself
        public static $syspar_db_name         = 'db_name_appsystem';
        public static $syspar_db_tablename    = 'db_name_companyfile';
        public static $syspar_host       = 'localhost';
        public static $syspar_username   = 'lstuser_lennard';
        public static $syspar_password   = 'lstV@2021';

        //FOR DB CONN ON DB CONTAINING ALL THE DBS containing the companyfile
        //OR normal DB connection
        
        public static $dbholder_db_name  = 'lrdfiles';
        public static $dbholder_host     = 'localhost';
        public static $dbholder_username = 'lstuser_lennard';
        public static $dbholder_password = 'lstV@2021';

        //needed for either Y for one DB 
        //N for multiple DBS
        public static $dbmode_singledb   = 'Y';

    }

?>

