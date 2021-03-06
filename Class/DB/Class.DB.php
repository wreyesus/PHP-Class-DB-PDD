<?php
					/*//////////////////////////////////////////////////////////
					//                                                        //
					//  Clase:  Conector Mysql,Pgsql,Oracle,ODBC              //
					//  Desarrollador:  Andrés Felipe Parra Ferreira          //
					//  Fecha:     19-11-2016                                 //
					//  Descripción:   Conector multi base de datos           //
					//  WebSite: http://www.wookplay.com                      //
					//////////////////////////////////////////////////////////*/

      class DataBase{
        private $conexion; private $total_consultas;
        //creación del constructor
        public function __construct($DB_TIPE,$DB_HOST,$DB_NAME,$DB_USER,$DB_PASS,$DB_PORT) {
          $this->tipo     = $DB_TIPE;
          $this->host     = $DB_HOST;
          $this->dbname   = $DB_NAME;
          $this->username = $DB_USER;
          $this->pass     = $DB_PASS;
          $this->port     = $DB_PORT;
          $this->conexion($DB_TIPE,$DB_HOST,$DB_NAME,$DB_USER,$DB_PASS,$DB_PORT);

        }
        public function conexion($DB_TIPE,$DB_HOST,$DB_NAME,$DB_USER,$DB_PASS,$DB_PORT){
          switch ($DB_TIPE) {
              case 'mysql':
                    if($DB_PORT<>Null){
                      $DB_HOST.=":".$DB_PORT;
                    }
                    $this->conexion=mysqli_connect($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME);
                    if (mysqli_connect_errno()){ return "Error en la Conexión: " . mysqli_connect_error(); }
                  break;
              case 'pgsql':
                    if(!isset($this->conexion)){
                      $this->conexion = (pg_connect("host=$DB_HOST port=$DB_PORT dbname=$DB_NAME user=$DB_USER password=$DB_PASS")) or die("Error en la Conexión: ".pg_last_error());
                    }
                  break;
              case 'oracle':
                    $this->conexion = oci_connect($DB_USER, $DB_PASS, $DB_HOST);
                    if (!$this->conexion) { print ("no se pudo conectar a Oracle"); }
                  break;
              case 'odbc':
                    $this->conexion= odbc_connect($DB_HOST, $DB_USER, $DB_PASS);
                    if (!$this->conexion){ return 'Error al conectar ODBC'.$DB_HOST; }
                  break;
          }
        }
        public function select($sql){
          switch ($this->tipo) {
              case 'mysql':
                  $result = mysqli_query($this->conexion,$sql);
                  $res    = mysqli_fetch_all($result,MYSQLI_ASSOC);
                  return $res;
                  break;
              case 'pgsql':
                  $execut    = pg_query($this->conexion, $sql);
                  $res       = pg_fetch_all($execut);
                  return     $res;
                  break;
              case 'oracle':
                  $consult = oci_parse($this->conexion, $sql);
                  oci_execute($consult);
                  $nrows 	 = oci_fetch_all($consult, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
                  return $res;
                  break;
              case 'odbc':
                  $result   =   odbc_exec($this->conexion,$sql)or die(exit("Error en consulta"));
                  $row      = 0;
                  $datos    = '';
                  while (odbc_fetch_row($result)) {
                     for ($j = 1; $j <= odbc_num_fields($result); $j++) {
                         $campo_tabla                 = odbc_field_name($result, $j);
                         $datos[$row][$campo_tabla]   = odbc_result($result, $campo_tabla);
                     }
                      $row++;
                  }
                  return $datos;
                  break;

          }

        }
        public function select_sql($BD,$SQL){
          switch ($this->tipo) {
              case 'mysql':
                  conexion($this->tipo,$this->host,$BD,$this->username,$this->pass,$this->port);
                  $result = mysqli_query($this->conexion,$SQL);
                  $res    = mysqli_fetch_all($result,MYSQLI_ASSOC);
                  conexion($this->tipo,$this->host,$this->dbname,$this->username,$this->pass,$this->port);
                  return $res;
                  break;
              case 'pgsql':
                  $execut    = pg_prepare($this->conexion, $bd, $sql);
                  $res       = pg_fetch_all($execut);
                  return     $res;
                  break;
              case 'oracle':
                  conexion($this->tipo,$this->host,$BD,$this->username,$this->pass,$this->port);
                  $consult = oci_parse($this->conexion, $sql);
                  oci_execute($consult);
                  $nrows 	 = oci_fetch_all($consult, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
                  conexion($this->tipo,$this->host,$this->dbname,$this->username,$this->pass,$this->port);
                  return $res;
                  break;
          }
        }
        public function insert($table,$data){
          switch ($this->tipo) {
            case 'mysql':
                ksort($data);
                $fieldDetails = NULL;
                $fieldNames = implode('`, `',  array_keys($data));
                $fieldValues = "" . implode("', '",  array_values($data));
                $sth = "INSERT INTO $table (`$fieldNames`) VALUES ('$fieldValues')";
                if (mysqli_query($this->conexion,$sth)) { return 'true'; }else{ return 'false';}
                break;
            case 'pgsql':
                $res = pg_insert($this->conexion, $table, $data);
                if ($res) {
                  return 'true';
                } else {
                  return 'false';
                }
                break;
            case 'oracle':

                return $res;
                break;
            case 'odbc':
                ksort($data);
                $fieldDetails   = NULL;
                $fieldNames     = implode('`, `',  array_keys($data));
                $fieldValues    = "" . implode("', '",  array_values($data));
                $sql            = "INSERT INTO $table (`$fieldNames`) VALUES ('$fieldValues')";
                $res            =   odbc_exec($this->conexion,$sql)or die( exit("Error al insertar datos") );  
                return $res;
                break;
          }
        }
        public function update($table,$data,$where){
          switch ($this->tipo) {
            case 'mysql':
                $wer = '';
                if(is_array($where)){
                  foreach ($where as $clave=>$valor){
                    $wer.= $clave."='".$valor."' AND ";
                  }
                  $wer   = substr($wer, 0, -4);
                  $where = $wer;
                }
                ksort($data);
                $fieldDetails = NULL;
                foreach ($data as $key => $values){
                    $fieldDetails .= "$key='$values',";
                }
                $fieldDetails = rtrim($fieldDetails,',');
                if($where==NULL or $where==''){
                  $sth = "UPDATE $table SET $fieldDetails";
                }else {
                  $sth = "UPDATE $table SET $fieldDetails WHERE $where";
                }
                if (mysqli_query($this->conexion,$sth)) { return 'true'; }else{ return 'false';}
                break;

            case 'pgsql':
                if(is_array($where)){
                }else{
                  $bucar 		   = ['AND','ANd','And','aND','anD','AnD'];
                  $reemplazar      = ['and','and','and','and','and','and'];
                  $str			   = str_replace($bucar , $reemplazar , $where);
                  $str2            = explode("and",$str);
                  $cant            = count($str2);
                  for ($a=0; $a <$cant ; $a++) {
                    $info            = trim($str2[$a]);
                    $bucar 		       = [''."'".'','"'];
                    $reemplazar      = ['',''];
                    $info			       = str_replace($bucar , $reemplazar , $info);
                    $str3            = explode("=",$info);
                    $field           = $str3[0];
                    $value           = $str3[1];
                    $filter [$field] = $value;
                  }
                    $where=$filter;
                }
                $res = pg_update($this->conexion, $table, $data,$where);
                if ($res) {
                  return 'true';
                } else {
                  return 'false';
                }
                break;
            case 'oracle':
                $consult = oci_parse($this->conexion, $sql);
                oci_execute($consult);
                $nrows 	 = oci_fetch_all($consult, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
                return $res;
                break;
            case 'odbc':
                if(is_array($where)){
                  foreach ($where as $clave=>$valor){
                    $wer.= $clave."='".$valor."' AND ";
                  }
                  $wer   = substr($wer, 0, -4);
                  $where = $wer;
                }
                ksort($data);
                $fieldDetails = NULL;
                foreach ($data as $key => $values){
                    $fieldDetails .= "$key='$values',";
                }
                $fieldDetails = rtrim($fieldDetails,',');
                if($where==NULL or $where==''){
                  $sql = "UPDATE $table SET $fieldDetails";
                }else {
                  $sql = "UPDATE $table SET $fieldDetails WHERE $where";
                }
                $res            =   odbc_exec($this->conexion,$sql)or die(exit("Error al actualizar datos ".$sql)); 
                return $res;
                break;
          }
        }
        public function delete($table,$where){

          switch ($this->tipo) {
            case 'mysql':
                $wer = '';
                if(is_array($where)){
                  foreach ($where as $clave=>$valor){
                    $wer.= $clave."='".$valor."' and ";
                  }
                  $wer   = substr($wer, 0, -4);
                  $where = $wer;
                }
                if($where==NULL or $where==''){
                  $sth = "DELETE FROM $table";
                  if (mysqli_query($this->conexion,$sth)) { return 'true'; }else{ return 'false';}
                }else{
                  $sth = "DELETE FROM $table WHERE $where";
                  if (mysqli_query($this->conexion,$sth)) { return 'true'; }else{ return 'false';}
                }
                break;
            case 'pgsql':
                if(is_array($where)){
                }else{
                  if($where<>''){
                    $bucar 		       = ['AND','ANd','And','aND','anD','AnD'];
                    $reemplazar      = ['and','and','and','and','and','and'];
                    $str			       = str_replace($bucar , $reemplazar , $where);
                    $str2            = explode("and",$str);
                    $cant            = count($str2);
                    for ($a=0; $a <$cant ; $a++) {
                      $info            = trim($str2[$a]);
                      $bucar 		       =[''."'".'','"'];
                      $reemplazar      =['',''];
                      $info			       = str_replace($bucar , $reemplazar , $info);
                      $str3            = explode("=",$info);
                      $field           = $str3[0];
                      $value           = $str3[1];
                      $filter [$field] = $value;
                    }
                      $where=$filter;
                  }else{
                    $where='';
                  }
                }
                if($where=='' or $where==Null){
                  $query = 'Delete from "public"."'.$table.'"';
                  $res = pg_query($this->conexion,$query);
                }else{
                  $res = pg_delete($this->conexion, $table,$where);
                }
                if ($res) {
                  echo 'true';
                } else {
                  echo 'false';
                }
                break;
            case 'oracle':
                $consult = oci_parse($this->conexion, $sql);
                oci_execute($consult);
                $nrows 	 = oci_fetch_all($consult, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
                return $res;
                break;
            case 'odbc':
                $wer = '';
                if(is_array($where)){
                  foreach ($where as $clave=>$valor){
                    $wer.= $clave."='".$valor."' and ";
                  }
                  $wer   = substr($wer, 0, -4);
                  $where = $wer;
                }
                if($where==NULL or $where==''){
                  $sql = "DELETE FROM $table";
                  $res =   odbc_exec($this->conexion,$sql)or die(exit("false")); 
                }else{
                  $sql = "DELETE FROM $table WHERE $where";
                  $res =   odbc_exec($this->conexion,$sql)or die(exit("false")); 
                }
                return 'true';            
                break;
          }
        }

      }

?>
