<?php

require_once 'DatabaseConnection.php';
require_once 'DatabaseConfiguration.php';

try {
    $db = new DatabaseConnection('PDO'); // o 'MYSQLI'
    $db->connect($dbHost, $dbName, $dbUser, $dbPass, $dbPort);

    if ($db->isConnected()) 
    {

        $results = $db->executeQueryPro("SELECT * FROM tb_accidentes_trabajos");

        //echo json_encode($results);


        $rowsUpdated = $db->executeQueryPro(
            "UPDATE tb_accidentes_trabajos SET usuario_creacion = ? WHERE id_accidente = ?", 
            ['jvilchis', 4]
        );
        echo "Rows updated: $rowsUpdated";
        

        //print_r($results);
    }

    $db->disconnect();
}
catch (Exception $e) 
{
    echo $e->getMessage();
}

?>