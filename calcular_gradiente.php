<?php
function calcular_gradiente($w, $x, $y, $funcion_costo) {
    switch ($funcion_costo) {
        case 'cuadratica':
            return 2 * ($w * $x - $y) * $x;
        case 'absoluta':
            return ($w * $x > $y) ? $x : (($w * $x < $y) ? -$x : 0);
        default:
            throw new Exception("Función de costo no reconocida");
    }
}

function calcular_funcion_costo($w, $x, $y, $funcion_costo) {
    switch ($funcion_costo) {
        case 'cuadratica':
            return pow($w * $x - $y, 2);
        case 'absoluta':
            return abs($w * $x - $y);
        default:
            throw new Exception("Función de costo no reconocida");
    }
}

// Procesar formulario si se envió
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $num_iteraciones = isset($_POST['num_iteraciones']) ? intval($_POST['num_iteraciones']) : null;
    $tasa_aprendizaje = isset($_POST['tasa_aprendizaje']) ? floatval($_POST['tasa_aprendizaje']) : null;
    $funcion_costo = isset($_POST['funcion_costo']) ? $_POST['funcion_costo'] : null;
    $tipo_gradiente = isset($_POST['tipo_gradiente']) ? $_POST['tipo_gradiente'] : null;
    $tamano_lote = isset($_POST['tamano_lote']) ? intval($_POST['tamano_lote']) : null;

    // Verificar si todos los campos están seteados
    if ($num_iteraciones === null || $tasa_aprendizaje === null || 
        $funcion_costo === null || $tipo_gradiente === null || $tamano_lote === null) {
        echo "<p>Error: Por favor, complete todos los campos.</p>";
    } else {
        // Valores iniciales
        $w = 0; // Peso inicial
        $x = array(); // Valores de x
        $y = array(); // Valores de y
        
        // Generar datos aleatorios
        for ($i = 0; $i < 100; $i++) {
            $x[] = rand(1, 100) / 10;
            $y[] = rand(1, 200) / 10;
        }

        echo "<h2>Resultados:</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Iteración</th><th>Peso (w)</th><th>Función Costo</th></tr>";

        $costos = array();
        
        for ($i = 0; $i < $num_iteraciones; $i++) {
            if ($tipo_gradiente == 'batch') {
                $gradiente_total = 0;
                $costo_total = 0;
                
                foreach ($x as $index => $valor_x) {
                    $gradiente = calcular_gradiente($w, $valor_x, $y[$index], $funcion_costo);
                    $costo = calcular_funcion_costo($w, $valor_x, $y[$index], $funcion_costo);
                    
                    $gradiente_total += $gradiente;
                    $costo_total += $costo;
                }
                
                $w -= $tasa_aprendizaje * ($gradiente_total / count($x));
                $costo_promedio = $costo_total / count($x);
            } elseif ($tipo_gradiente == 'estocastico') {
                $indices_lote = array_rand($x, min($tamano_lote, count($x)));
                if (!is_array($indices_lote)) {
                    $indices_lote = array($indices_lote);
                }
                
                $gradiente_total = 0;
                $costo_total = 0;
                
                foreach ($indices_lote as $index) {
                    $gradiente = calcular_gradiente($w, $x[$index], $y[$index], $funcion_costo);
                    $costo = calcular_funcion_costo($w, $x[$index], $y[$index], $funcion_costo);
                    
                    $gradiente_total += $gradiente;
                    $costo_total += $costo;
                }
                
                $w -= $tasa_aprendizaje * ($gradiente_total / count($indices_lote));
                $costo_promedio = $costo_total / count($indices_lote);
            }
            
            echo "<tr><td>" . ($i + 1) . "</td><td>" . round($w, 4) . "</td><td>" . round($costo_promedio, 4) . "</td></tr>";
            $costos[] = round($costo_promedio, 4);
        }

        echo "</table>";

        // Preparar datos para gráfico
        $etiquetas = range(1, $num_iteraciones);
        $costos = array_map('floatval', $costos);
        $datos_costo = json_encode($costos);
        $etiquetas_json = json_encode($etiquetas);
        
        // Mostrar script para crear gráfico
        echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>";
        echo "<div><canvas id='myChart' width='400' height='200'></canvas></div>";
        echo "<script>
            const ctx = document.getElementById('myChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: $etiquetas_json,
                    datasets: [{
                        label: 'Función de Costo',
                        data: $datos_costo,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>";

        // Mostrar estadísticas finales
        echo "<h3>Estadísticas Finales:</h3>";
        echo "Peso final (w): " . round($w, 4) . "<br>";
        echo "Costo promedio final: " . round(array_sum($costos) / count($costos), 4);
    }
}
?>
