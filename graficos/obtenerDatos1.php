<?php

require_once __DIR__ . '/../_lib/utils/DatabaseConnection.php';
require_once __DIR__ . '/../_lib/utils/DatabaseConfiguration.php';

try {
    $db = new DatabaseConnection('PDO'); // o 'MYSQLI'
    $db->connect($dbHost, $dbName, $dbUser, $dbPass, $dbPort);

    if ($db->isConnected()) 
    {
        $jsonMaestro = [
            "graficas" => []
        ];

        $check_sql = "
        SELECT 
            descripcion_linea, nombre_producto, tarimas_realizadas
        FROM
            tb_cabecero_producciones
        WHERE
            fecha_produccion = ?
        ";
        $results = $db->executeQueryPro($check_sql, ['2024-12-02']);

        $jsonMaestro["graficas"][] = GenerarJsonProduccionPorLinea($results);
        $jsonMaestro["graficas"][] = GenerarJsonProduccionPorProducto($results);


        $check_sql = "
        SELECT 
            nombre_producto,
            SUM(no_tarimas) AS cantidad_planificada,
            SUM(tarimas_realizadas) AS cantidad_producida
        FROM
            tb_cabecero_producciones
        WHERE
            fecha_produccion = ?
        GROUP BY nombre_producto
        ";
        $results = $db->executeQueryPro($check_sql, ['2024-12-02']);

        $jsonMaestro["graficas"][] = GenerarJsonReporteProducto($results);

        header('Content-Type: application/json');
        echo json_encode($jsonMaestro, JSON_PRETTY_PRINT);
        //echo json_encode($results);
    }

    $db->disconnect();
} catch (Exception $e) {
    echo $e->getMessage();
}

function GenerarJsonProduccionPorLinea($results) {
    $lineas = [];

    foreach ($results as $row) {
        $linea = $row['descripcion_linea'];
        $tarimasRealizadas = (int) $row['tarimas_realizadas'];

        if (isset($lineas[$linea])) {
            $lineas[$linea] += $tarimasRealizadas;
        } else {
            $lineas[$linea] = $tarimasRealizadas;
        }
    }

    return [
        "titulo" => "Producción por Línea",
        "tipoGrafico" => "bar",
        "datos" => [
            "labels" => array_keys($lineas),
            "datasets" => [
                [
                    "label" => "Tarimas Realizadas",
                    "data" => array_values($lineas),
                    "backgroundColor" => "rgba(75, 192, 192, 0.5)",
                    "borderColor" => "rgba(75, 192, 192, 1)",
                    "borderWidth" => 1
                ]
            ]
        ],
        "colorFondo" => "bg-blue-500"
    ];
}

function GenerarJsonProduccionPorProducto($results) {
    $productos = [];

    foreach ($results as $row) {
        $producto = $row['nombre_producto'];
        $tarimasRealizadas = (int) $row['tarimas_realizadas'];

        if (isset($productos[$producto])) {
            $productos[$producto] += $tarimasRealizadas;
        } else {
            $productos[$producto] = $tarimasRealizadas;
        }
    }

    return [
        "titulo" => "Producción por Producto",
        "tipoGrafico" => "pie",
        "datos" => [
            "labels" => array_keys($productos),
            "datasets" => [
                [
                    "label" => "Cantidad",
                    "data" => array_values($productos),
                    "backgroundColor" => [
                        "rgba(255, 99, 132, 0.5)",
                        "rgba(54, 162, 235, 0.5)",
                        "rgba(255, 206, 86, 0.5)"
                    ],
                    "borderColor" => [
                        "rgba(255, 99, 132, 1)",
                        "rgba(54, 162, 235, 1)",
                        "rgba(255, 206, 86, 1)"
                    ],
                    "borderWidth" => 1
                ]
            ]
        ],
        "colorFondo" => "bg-green-500"
    ];
}

function GenerarJsonReporteProducto($results) {
    $productos = [];

    // Procesar los resultados
    foreach ($results as $row) {
        $producto = $row['nombre_producto'];
        $planificado = (int)$row['cantidad_planificada'];
        $producido = (int)$row['cantidad_producida'];

        // Almacenar datos
        $productos[$producto] = [
            'planificado' => $planificado,
            'producido' => $producido,
        ];
    }

    // Crear estructura del gráfico
    $labels = array_keys($productos); // Nombres de los productos
    $planificados = array_column($productos, 'planificado');
    $producidos = array_column($productos, 'producido');

    $json = [
        "titulo" => "Comparación Planificado vs Producido",
        "tipoGrafico" => "bar",
        "datos" => [
            "labels" => $labels,
            "datasets" => [
                [
                    "label" => "Planificado",
                    "data" => $planificados,
                    "backgroundColor" => "rgba(54, 162, 235, 0.5)",
                    "borderColor" => "rgba(54, 162, 235, 1)",
                    "borderWidth" => 1
                ],
                [
                    "label" => "Producido",
                    "data" => $producidos,
                    "backgroundColor" => "rgba(255, 99, 132, 0.5)",
                    "borderColor" => "rgba(255, 99, 132, 1)",
                    "borderWidth" => 1
                ]
            ]
        ],
        "colorFondo" => "bg-gray-100"
    ];

    return $json;
}

?>
