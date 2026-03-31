<?php
// EJEMPLO: content/works/eyre_content.php/test-ejemplo.php
// Ejemplo de cómo integrar el rastreador de progreso en una actividad de Jane Eyre

session_start();
require '../../../../activity-tracker.php';

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Jane Eyre - Litterally</title>
    <link rel="icon" href="../../../../media/images/iconoPestanaClara.png">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .question {
            background: #f9f9f9;
            padding: 20px;
            border-left: 4px solid #4CAF50;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .question h3 {
            color: #333;
            margin-top: 0;
        }
        
        .option {
            margin: 10px 0;
        }
        
        input[type="radio"] {
            margin-right: 10px;
        }
        
        label {
            color: #333;
        }
        
        .submit-btn {
            background: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
            transition: background 0.3s;
        }
        
        .submit-btn:hover {
            background: #45a049;
        }
        
        .back-link {
            display: block;
            margin-top: 30px;
            text-align: center;
        }
        
        .back-link a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 600;
        }
        
        .score-display {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            display: none;
        }
        
        .score-display.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="margin-bottom: 30px;">
            <a href="../eyre.php" style="color: #4CAF50; text-decoration: none; font-weight: 600;">← Volver a Jane Eyre</a>
        </div>
        
        <h1>📋 Test de Comprensión</h1>
        <p class="subtitle">Jane Eyre - Capítulo 1-4 (Infancia en Gateshead)</p>
        
        <!-- Mostrar widget de progreso -->
        <?php mostrarWidgetProgreso(); ?>
        
        <form id="test-form">
            <!-- Pregunta 1 -->
            <div class="question">
                <h3>1. ¿Dónde pasa la infancia de Jane Eyre?</h3>
                <div class="option">
                    <input type="radio" id="q1-a" name="q1" value="a">
                    <label for="q1-a">En Lowood School</label>
                </div>
                <div class="option">
                    <input type="radio" id="q1-b" name="q1" value="b">
                    <label for="q1-b">En Gateshead Hall</label>
                </div>
                <div class="option">
                    <input type="radio" id="q1-c" name="q1" value="c">
                    <label for="q1-c">En Thornfield</label>
                </div>
            </div>
            
            <!-- Pregunta 2 -->
            <div class="question">
                <h3>2. ¿Quién era el tío de Jane que le prometió protección?</h3>
                <div class="option">
                    <input type="radio" id="q2-a" name="q2" value="a">
                    <label for="q2-a">John Reed</label>
                </div>
                <div class="option">
                    <input type="radio" id="q2-b" name="q2" value="b">
                    <label for="q2-b">El señor Reed</label>
                </div>
                <div class="option">
                    <input type="radio" id="q2-c" name="q2" value="c">
                    <label for="q2-c">Bessie</label>
                </div>
            </div>
            
            <!-- Pregunta 3 -->
            <div class="question">
                <h3>3. ¿Cómo se llama el cuarto donde encierra a Jane?</h3>
                <div class="option">
                    <input type="radio" id="q3-a" name="q3" value="a">
                    <label for="q3-a">El cuarto azul</label>
                </div>
                <div class="option">
                    <input type="radio" id="q3-b" name="q3" value="b">
                    <label for="q3-b">El cuarto rojo</label>
                </div>
                <div class="option">
                    <input type="radio" id="q3-c" name="q3" value="c">
                    <label for="q3-c">El cuarto negro</label>
                </div>
            </div>
            
            <!-- Score -->
            <div class="score-display" id="score-display">
                <div style="font-size: 24px; font-weight: bold; color: #2e7d32; margin-bottom: 10px;">
                    Tu puntuación: <span id="score-value">0</span>/100
                </div>
                <p style="margin: 0; color: #666;">Presiona el botón de abajo para guardar tu progreso</p>
            </div>
            
            <button type="button" class="submit-btn" onclick="verificarRespuestas()">
                Verificar Respuestas
            </button>
        </form>
        
        <!-- Mostrar el JavaScript de progreso -->
        <?php mostrarScriptProgreso(); ?>
        
        <div class="back-link">
            <a href="../eyre.php">← Volver a Jane Eyre</a>
        </div>
    </div>
    
    <script>
    let puntuacionActual = 0;
    
    function verificarRespuestas() {
        // Respuestas correctas
        const respuestasCorrectas = {
            'q1': 'b',  // Gateshead Hall
            'q2': 'b',  // El señor Reed
            'q3': 'b'   // El cuarto rojo
        };
        
        // Contar respuestas correctas
        let correctas = 0;
        for (let pregunta in respuestasCorrectas) {
            const respuestaSeleccionada = document.querySelector('input[name="' + pregunta + '"]:checked');
            if (respuestaSeleccionada && respuestaSeleccionada.value === respuestasCorrectas[pregunta]) {
                correctas++;
            }
        }
        
        // Calcular puntuación
        puntuacionActual = Math.round((correctas / 3) * 100);
        
        // Mostrar resultado
        document.getElementById('score-value').textContent = puntuacionActual;
        document.getElementById('score-display').classList.add('show');
        
        // Deshabilitar inputs
        document.querySelectorAll('input[type="radio"]').forEach(input => {
            input.disabled = true;
        });
    }
    
    // Reemplazar el botón de verificar con el de guardar
    function mostrarBotonGuardar() {
        const btn = document.querySelector('.submit-btn');
        btn.onclick = function() {
            guardarProgreso(1, puntuacionActual, 'Test - Capítulo 1-4');
        };
        btn.textContent = '✓ Guardar Progreso';
    }
    </script>
</body>
</html>
