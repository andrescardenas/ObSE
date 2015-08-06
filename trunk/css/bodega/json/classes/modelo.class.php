<?php

//include('../BD.php');

class Modelo {

    private $data;

    public function crear($table, $values) {
        include('../BD.php');
        $fieldnames = array_keys($values[0]);
        /*         * * now build the query ** */
        $size = sizeof($fieldnames);
        $i = 1;
        $sql = "INSERT INTO $table";
        /*         * * set the field names ** */
        $fields = '( ' . implode(' ,', $fieldnames) . ' )';
        /*         * * set the placeholders ** */
        $bound = '(:' . implode(', :', $fieldnames) . ' )';
        /*         * * put the query together ** */
        $sql .= $fields . ' VALUES ' . $bound;
        //seguimiento
        $this->escribirLog("crear...(".$sql.")");
        foreach ($values[0] as $key => $value) {
            $this->escribirLog("- $key($value)<br/>");
        }
        /*         * * prepare and execute ** */
        $stmt = $BD->prepare($sql);
        foreach ($values as $vals) {
            $stmt->execute($vals);
        }

        $BD = null;
    }

    public function editarTodo($table, $values) {
        include('../BD.php'); 
        $id = $values[0]['id'];
        $this->escribirLog("editarTodo...(".$table.")");
        foreach ($values[0] as $key => $value) { $this->escribirLog("- $key($value){{".gettype($value)."[[[".strcmp('',$value)."]]]}}<br/>"); }
        $this->escribirLog("<br/>--------::--------::-----------<br/>");
        foreach ($values as $vs) {
            foreach ($vs as $key => $value) {
                if ( strcmp('',$value) != 0 && strcmp($key, 'id') != 0) $this->editar($table,$key,str_replace("'","''",$value),'id',$id);
                else if ( strcmp('',$value) == 0 ) $this->editarNulo($table, $key,'id',$id);
            }
        }
        $BD = null;
    }

    public function editar($table, $fieldname, $value, $pk, $id) {
        include('../BD.php');
        $sql = "UPDATE `$table` SET `$fieldname`='{$value}' WHERE `$pk` = :id";
        $stmt = $BD->prepare($sql);
        $this->escribirLog("++editar...(".$table.": ".$sql.")");
        foreach ($values[0] as $key => $value) { $this->escribirLog("- $key($value)<br/>"); }
        $this->escribirLog("<br/><br/>");
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        $BD = null;
    }

    public function editarNulo($table, $fieldName, $pkName, $pkValue){
        $this->escribirLog("- --------------------- <br/>");
        include('../BD.php');
        $SQL = "UPDATE ".$table." SET ".$fieldName." = NULL WHERE ".$pkName." = ".$pkValue;
        $BD->query($SQL);
        $this->escribirLog("- editarNulo[[[".$SQL."]]]<br/>");
        $BD = null;
    }

    public function eliminar($table, $fieldname, $id) {
$this->escribirLog("<br /> eliminar (".$table.") - id:$id");
        include('../BD.php');
        $sql = "DELETE FROM `$table` WHERE `$fieldname` = :id";
        $stmt = $BD->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        $BD = null;
    }

    public function abrirLog() {
        $this->data .= "<br />" . date('m/d/Y h:i:s a', time()) . " <br />";
    }

    public function escribirLog($mensaje) {
        $this->data .= " - - "
                . "," . $mensaje;
    }

    public function cerrarLog() {
        $file = "data.html";
        $fp = fopen($file, "a") or die("Couldn't open $file for login!");
        fwrite($fp, $this->data) or die("Couldn't open new page!");
        fclose($fp);
    }

    public function arregloEnLog($arreglo) {
        $this->escribirLog("<br />:::");
        foreach ($arreglo as $key => $value) {
            $this->escribirLog("(" . $key . " --> " . $value . ")");
        }
        $this->escribirLog(":::");
    }

    public function ultimoIdTabla($nombreTabla) {
        include('../BD.php');
        $lastId = 0;

        $SQL = "SELECT id FROM `" . $nombreTabla . "` ORDER BY `id` DESC LIMIT 1 ";
        foreach ($BD->query($SQL) as $key => $value)
            foreach ($value as $total)
                $lastId = $total;
        $BD = null;
        return $lastId;

    }

    public function idDatoTablaS2($table, $nColumn1, $vColumn1, $nColumn2, $vColumn2){
        include('../BD.php');
        $sql = "SELECT id FROM $table WHERE $nColumn1 = $vColumn1 AND $nColumn2 = $vColumn2";
        foreach ($BD->query($sql) as $result) {
            return $result[id];
        }
        $BD = null;
        return -1;
    }

}
