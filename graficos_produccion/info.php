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

        $jsonMaestro["graficas"][] = ObtenerProduccionPorLinea($db);
        $jsonMaestro["graficas"][] = ObtenerCantidadProducidaVsPlanificada($db);
        $jsonMaestro["graficas"][] = ObtenerProduccionPorProducto($db);
        $jsonMaestro["graficas"][] = ObtenerRendimientoPorLinea($db);

        header('Content-Type: application/json');
        echo json_encode($jsonMaestro, JSON_PRETTY_PRINT);
    }

    $db->disconnect();
} catch (Exception $e) {
    echo $e->getMessage();
}

function obtenerTema($tema) {
    $temas = [
        "Colorido" => [
            "backgroundColor" => ["rgba(255, 99, 132, 0.5)", "rgba(54, 162, 235, 0.5)", "rgba(255, 206, 86, 0.5)", "rgba(75, 192, 192, 0.5)", "rgba(153, 102, 255, 0.5)", "rgba(255, 159, 64, 0.5)"],
            "borderColor" => ["rgba(255, 99, 132, 1)", "rgba(54, 162, 235, 1)", "rgba(255, 206, 86, 1)", "rgba(75, 192, 192, 1)", "rgba(153, 102, 255, 1)", "rgba(255, 159, 64, 1)"]
        ],
        "Oscuro" => [
            "backgroundColor" => ["rgba(33, 33, 33, 0.7)", "rgba(77, 77, 77, 0.7)", "rgba(0, 0, 0, 0.7)"],
            "borderColor" => ["rgba(255, 255, 255, 1)"]
        ],
        "Innovador" => [
            "backgroundColor" => ["rgba(0, 123, 255, 0.5)", "rgba(23, 162, 184, 0.5)"],
            "borderColor" => ["rgba(0, 123, 255, 1)", "rgba(255, 255, 255, 1)"]
        ],
        "Pastel" => [
            "backgroundColor" => ["rgba(255, 183, 197, 0.5)", "rgba(186, 230, 255, 0.5)"],
            "borderColor" => ["rgba(255, 183, 197, 1)", "rgba(186, 230, 255, 1)"]
        ],
        "Neon" => [
            "backgroundColor" => ["rgba(255, 0, 255, 0.5)", "rgba(0, 255, 255, 0.5)", "rgba(0, 255, 0, 0.5)"],
            "borderColor" => ["rgba(255, 0, 255, 1)", "rgba(0, 255, 255, 1)", "rgba(0, 255, 0, 1)"]
        ]
    ];

    return $temas[$tema] ?? $temas["Colorido"];
}

function personalizarGrafico($tipoGrafico, $datasets) {
    switch ($tipoGrafico) {
        case "bar":
            foreach ($datasets as &$dataset) {
                $dataset["borderRadius"] = 10; // Barras redondeadas
                $dataset["hoverBackgroundColor"] = "rgba(255, 255, 255, 0.8)";
                $dataset["hoverBorderColor"] = "rgba(0, 0, 0, 1)";
            }
            break;
        case "line":
            foreach ($datasets as &$dataset) {
                $dataset["pointStyle"] = "circle"; // Estilo de puntos
                $dataset["pointRadius"] = 5; // Tamaño de puntos
                $dataset["pointHoverRadius"] = 8;
                $dataset["borderDash"] = [5, 5]; // Línea discontinua
            }
            break;
        case "pie":
            foreach ($datasets as &$dataset) {
                $dataset["hoverOffset"] = 10; // Separar porciones al pasar el ratón
            }
            break;
    }
    return $datasets;
}

function construirGrafica($titulo, $tipoGrafico, $labels, $datasets, $colorFondo) {
    $datasets = personalizarGrafico($tipoGrafico, $datasets);
    return [
        "titulo" => $titulo,
        "tipoGrafico" => $tipoGrafico,
        "datos" => [
            "labels" => $labels,
            "datasets" => $datasets
        ],
        "colorFondo" => $colorFondo
    ];
}

function ObtenerProduccionPorLinea($db)
{
    $check_sql = "
    SELECT 
        descripcion_linea, SUM(tarimas_realizadas) AS total_tarimas
    FROM
        tb_cabecero_producciones
    WHERE
        fecha_produccion = ?
    GROUP BY descripcion_linea
    ORDER BY total_tarimas DESC
    ";
    $results = $db->executeQueryPro($check_sql, ['2024-12-02']);

    $lineas = [];
    foreach ($results as $row) {
        $lineas[$row['descripcion_linea']] = (int) $row['total_tarimas'];
    }

    $datasets = [
        [
            "label" => "Tarimas Realizadas",
            "data" => array_values($lineas),
            "backgroundColor" => "rgba(153, 102, 255, 0.5)",
            "borderColor" => "rgba(153, 102, 255, 1)",
            "borderWidth" => 1
        ]
    ];

    return construirGrafica(
        "Producción por Línea",
        "bar",
        array_keys($lineas),
        $datasets,
        "bg-blue-500"
    );
}

function ObtenerCantidadProducidaVsPlanificada($db)
{
    $check_sql = "
    SELECT 
        nombre_producto,
        SUM(no_tarimas) AS cantidad_planificada,
        SUM(tarimas_realizadas) AS cantidad_producida
    FROM
        tb_cabecero_producciones
    WHERE
        fecha_produccion = ?
            AND no_lote <> ''
    GROUP BY nombre_producto
    ORDER BY SUM(no_tarimas) DESC
    ";
    $results = $db->executeQueryPro($check_sql, ['2024-12-02']);

    $productos = [];
    $planificado = [];
    $producido = [];

    foreach ($results as $row) {
        $productos[] = $row['nombre_producto'];
        $planificado[] = (int) $row['cantidad_planificada'];
        $producido[] = (int) $row['cantidad_producida'];
    }

    $datasets = [
        [
            "label" => "Planificado",
            "data" => $planificado,
            "backgroundColor" => "rgba(255, 99, 132, 0.5)",
            "borderColor" => "rgba(255, 99, 132, 1)",
            "borderWidth" => 1
        ],
        [
            "label" => "Producido",
            "data" => $producido,
            "backgroundColor" => "rgba(54, 162, 235, 0.5)",
            "borderColor" => "rgba(54, 162, 235, 1)",
            "borderWidth" => 1
        ]
    ];

    return construirGrafica(
        "Cantidad Producida vs Planificada",
        "bar",
        $productos,
        $datasets,
        "bg-green-500"
    );
}

function ObtenerProduccionPorProducto($db)
{
    $check_sql = "
    SELECT 
        nombre_producto, SUM(tarimas_realizadas) AS total_producido
    FROM
        tb_cabecero_producciones
    WHERE
        fecha_produccion = ?
    GROUP BY nombre_producto
    ORDER BY total_producido DESC
    ";
    $results = $db->executeQueryPro($check_sql, ['2024-12-02']);

    $productos = [];
    $producidos = [];

    foreach ($results as $row) {
        $productos[] = $row['nombre_producto'];
        $producidos[] = (int) $row['total_producido'];
    }

    $datasets = [
        [
            "label" => "Cantidad Producida",
            "data" => $producidos,
            "backgroundColor" => ["#FF6384", "#36A2EB", "#FFCE56"],
        ]
    ];

    return construirGrafica(
        "Producción por Producto",
        "pie",
        $productos,
        $datasets,
        "bg-yellow-500"
    );
}

function ObtenerRendimientoPorLinea($db)
{
    $check_sql = "
    SELECT 
        descripcion_linea,
        ROUND(SUM(tarimas_realizadas) / SUM(no_tarimas) * 100,
                2) AS porcentaje_cumplimiento
    FROM
        tb_cabecero_producciones
    WHERE
        fecha_produccion = ?
    GROUP BY descripcion_linea
    ORDER BY porcentaje_cumplimiento DESC
    ";
    $results = $db->executeQueryPro($check_sql, ['2024-12-02']);

    $lineas = [];
    $cumplimiento = [];

    foreach ($results as $row) {
        $lineas[] = $row['descripcion_linea'];
        $cumplimiento[] = (float) $row['porcentaje_cumplimiento'];
    }

    $datasets = [
        [
            "label" => "Porcentaje de Cumplimiento",
            "data" => $cumplimiento,
            "borderColor" => "rgba(75, 192, 192, 1)",
            "fill" => false
        ]
    ];

    return construirGrafica(
        "Rendimiento por Línea",
        "line",
        $lineas,
        $datasets,
        "bg-purple-500"
    );
}

?>
