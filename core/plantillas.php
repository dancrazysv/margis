<?php
// core/plantillas.php
class PlantillaManager {
    
    /**
     * Convierte el código de tipo asiento a texto legible
     */
    public static function getTipoAsientoTexto($tipo) {
        $tipos = [
            'NACIMIENTO' => 'nacimiento',
            'MATRIMONIO' => 'matrimonio',
            'DEFUNCION' => 'defunción',
            'UNION_NO_MATRIMONIAL' => 'unión no matrimonial',
            'DIVORCIO' => 'divorcio',
            'REGIMEN_PATRIMONIAL' => 'régimen patrimonial',
            'MARGINACION' => 'marginación',
            'TODOS' => 'asiento'
        ];
        return $tipos[$tipo] ?? 'asiento';
    }
    
    /**
     * Extrae las variables entre llaves {variable} de un texto.
     */
    public static function extraerVariables($texto) {
        preg_match_all('/\{(.*?)\}/', $texto, $matches);
        return array_unique($matches[1]);
    }
    
    /**
     * Obtiene todas las plantillas activas
     */
    public static function getPlantillasActivas($conn, $tipo_asiento = null) {
        if ($tipo_asiento) {
            $sql = "SELECT id, nombre_tramite, tipo_asiento, tipo_acto, tipo_marginacion, descripcion 
                    FROM plantillas_textos 
                    WHERE activo = 1 AND (tipo_asiento = ? OR tipo_asiento = 'TODOS')
                    ORDER BY tipo_asiento, nombre_tramite";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $tipo_asiento);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $sql = "SELECT id, nombre_tramite, tipo_asiento, tipo_acto, tipo_marginacion, descripcion 
                    FROM plantillas_textos 
                    WHERE activo = 1 
                    ORDER BY tipo_asiento, nombre_tramite";
            $result = $conn->query($sql);
            return $result->fetch_all(MYSQLI_ASSOC);
        }
    }
    
    /**
     * Obtiene una plantilla por ID con sus campos
     */
    public static function getPlantillaCompleta($conn, $plantilla_id) {
        // Obtener la plantilla
        $stmt = $conn->prepare("SELECT * FROM plantillas_textos WHERE id = ?");
        $stmt->bind_param("i", $plantilla_id);
        $stmt->execute();
        $plantilla = $stmt->get_result()->fetch_assoc();
        
        if (!$plantilla) {
            return null;
        }
        
        // Obtener sus campos
        $stmt = $conn->prepare("SELECT * FROM plantillas_campos WHERE plantilla_id = ? ORDER BY orden ASC");
        $stmt->bind_param("i", $plantilla_id);
        $stmt->execute();
        $plantilla['campos'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        return $plantilla;
    }
    
    /**
     * Renderiza el texto final reemplazando todas las variables
     */
    public static function renderizarTexto($texto, $datos, $sujeto_mat = 'EL', $nombre_el = '', $nombre_ella = '', $tipo_asiento_actual = 'NACIMIENTO') {
        // Variables predefinidas del sistema
        $reemplazos_base = [
            '{o_a}' => ($sujeto_mat === 'EL') ? 'o' : 'a',
            '{nombre_conyuge}' => ($sujeto_mat === 'EL') ? $nombre_ella : $nombre_el,
            '{fecha_hoy_letras}' => self::getFechaEnLetras(),
            '{lugar}' => $datos['lugar'] ?? '',
            '{tipo_asiento_texto}' => self::getTipoAsientoTexto($tipo_asiento_actual),
        ];
        
        // Agregar variables dinámicas
        foreach ($datos as $llave => $valor) {
            $reemplazos_base['{' . $llave . '}'] = $valor;
        }
        
        // Aplicar reemplazos
        $texto_final = str_replace(array_keys($reemplazos_base), array_values($reemplazos_base), $texto);
        
        // Limpieza de puntuación
        $texto_final = str_replace(', .', '.', $texto_final);
        $texto_final = str_replace(' .', '.', $texto_final);
        $texto_final = str_replace(' ,', ',', $texto_final);
        $texto_final = preg_replace('/\s+/', ' ', $texto_final);
        $texto_final = trim($texto_final);
        
        // Limpiar casos donde quede "ante " sin contenido
        $texto_final = str_replace('ante ,', '', $texto_final);
        $texto_final = str_replace('ante .', '', $texto_final);
        
        return $texto_final;
    }
    
    /**
     * Genera la leyenda de apellidos para la cónyuge
     */
    public static function armarLeyendaApellido($tipo, $nombre_el, $manual = "") {
        switch ($tipo) {
            case 'soltera':
                return "seguirá usando sus apellidos de soltera, ";
            case 'con_de':
                return "usará el apellido del cónyuge precedido de la partícula DE, ";
            case 'sin_de':
                return "usará el apellido del cónyuge sin la partícula DE, ";
            case 'exterior':
                return mb_strtoupper($manual, 'UTF-8') . ", ";
            default:
                return "";
        }
    }
    
    /**
     * Genera la fecha actual en formato legal (letras) para El Salvador
     */
/**
 * Genera la fecha actual en formato legal (letras) para El Salvador
 */
public static function getFechaEnLetras() {
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    
    $dia = (int)date('d');
    $mes = $meses[(int)date('n')];
    $anio = date('Y');
    
    // Cambiado: quitar strtoupper para que no salga en mayúsculas
    return "$dia de $mes de $anio";
}
    
    /**
     * Convierte una fecha a letras
     */
    public static function fechaALetras($fecha, $incluir_anio = true) {
        if (empty($fecha)) return '';
        
        $timestamp = strtotime($fecha);
        $dia = (int)date('d', $timestamp);
        $mes_num = (int)date('n', $timestamp);
        $anio = (int)date('Y', $timestamp);
        
        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
        ];
        
        $mes = $meses[$mes_num];
        
        if ($incluir_anio) {
            return strtoupper("$dia de $mes de $anio");
        }
        return strtoupper("$dia de $mes");
    }
    
    /**
     * Obtiene el siguiente ID digital para un año
     */
    public static function siguienteId($conn, $anio) {
        $stmt = $conn->prepare("SELECT MAX(CAST(NMargi1 AS UNSIGNED)) as u FROM margi WHERE LibroO = ?");
        $stmt->bind_param("s", $anio);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return ($row['u'] ?? 0) + 1;
    }
    
    /**
     * Guarda una marginación en la base de datos
     */
    public static function guardarMarginacion($conn, $datos, $anio_digital, $usuario, $tipo_p, $lugar, $anio_p, $libro_p, $folio_o, $num_tramite, $n_partida = '', $tomo_p = '') {
        $id_digital = self::siguienteId($conn, $anio_digital);
        $concatenado = $anio_digital . "--" . $id_digital;
        
        $sql = "INSERT INTO margi (
            LibroO, NMargi1, TxtMargi1, TipoP, lugar, AnioP, LibroP, NPartida, FolioO, TomoP,
            num_tramite, estado, usuario_creo, Iniciales1, libro_nmargi_concat, FechaC
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'DIGITADO', ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssssssssss",
            $anio_digital, $id_digital, $datos['texto'], $tipo_p, $lugar, $anio_p, 
            $libro_p, $n_partida, $folio_o, $tomo_p, $num_tramite, $usuario, $usuario, $concatenado
        );
        
        if ($stmt->execute()) {
            return $id_digital;
        }
        return false;
    }
}
?>