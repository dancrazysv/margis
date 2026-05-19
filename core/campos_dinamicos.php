<?php
// core/campos_dinamicos.php
require_once __DIR__ . '/../config/db.php';

class CamposDinamicos {
    
    /**
     * Genera el HTML para un campo según su tipo
     */
    public static function generarCampo($campo, $index, $valor_anterior = '') {
        $nombre = $campo['nombre_campo'];
        $etiqueta = $campo['etiqueta'];
        $tipo = $campo['tipo_campo'];
        $requerido = $campo['requerido'] ? 'required' : '';
        $ayuda = !empty($campo['ayuda']) ? '<div class="form-text">' . htmlspecialchars($campo['ayuda']) . '</div>' : '';
        $opciones = !empty($campo['opciones']) ? json_decode($campo['opciones'], true) : [];
        
        $html = '<div class="col-md-6 mb-3">';
        $html .= '<label class="form-label fw-bold small">' . htmlspecialchars($etiqueta) . '</label>';
        
        switch ($tipo) {
            case 'textarea':
                $html .= '<textarea name="vars[' . $nombre . ']" class="form-control" rows="3" ' . $requerido . '>' . htmlspecialchars($valor_anterior) . '</textarea>';
                break;
                
            case 'date':
                $html .= '<input type="date" name="vars[' . $nombre . ']" class="form-control" ' . $requerido . ' value="' . htmlspecialchars($valor_anterior) . '">';
                $html .= '<input type="hidden" name="vars[' . $nombre . '_letras]" id="letras_' . $nombre . '">';
                break;
                
            case 'fecha_letras':
                $html .= '<input type="date" name="vars[' . $nombre . ']" class="form-control fecha-a-letras" ' . $requerido . ' value="' . htmlspecialchars($valor_anterior) . '" data-target="letras_' . $nombre . '">';
                $html .= '<input type="hidden" name="vars[' . $nombre . '_letras]" id="letras_' . $nombre . '">';
                $html .= '<div class="form-text">La fecha se convertirá automáticamente a formato legal (Ej: 15 de enero de 2024)</div>';
                break;
                
            case 'select':
                $html .= '<select name="vars[' . $nombre . ']" class="form-select select2-init" ' . $requerido . '>';
                $html .= '<option value="">-- Seleccione --</option>';
                foreach ($opciones as $opcion) {
                    $selected = ($valor_anterior == $opcion) ? 'selected' : '';
                    $html .= '<option value="' . htmlspecialchars($opcion) . '" ' . $selected . '>' . htmlspecialchars($opcion) . '</option>';
                }
                $html .= '</select>';
                break;
                
            case 'funcionario':
                global $conn;
                $html .= '<div class="row g-2">';
                $html .= '<div class="col-md-6">';
                $html .= '<select name="vars_cargo[' . $nombre . ']" class="form-select select2-init cargo-sel" data-var="' . $nombre . '">';
                $html .= '<option value="">-- Seleccione Cargo --</option>';
                
                $cargos = $conn->query("SELECT cargo FROM cargo_juridico ORDER BY cargo ASC");
                while ($c = $cargos->fetch_assoc()) {
                    $html .= '<option value="' . htmlspecialchars($c['cargo']) . '">' . htmlspecialchars($c['cargo']) . '</option>';
                }
                $html .= '</select>';
                $html .= '</div><div class="col-md-6">';
                $html .= '<select name="vars_nombre[' . $nombre . ']" class="form-select select2-init nombre-sel" data-var="' . $nombre . '">';
                $html .= '<option value="">-- Seleccione Funcionario --</option>';
                
                $notarios = $conn->query("SELECT nombre FROM notarios ORDER BY nombre ASC");
                while ($n = $notarios->fetch_assoc()) {
                    $html .= '<option value="' . htmlspecialchars($n['nombre']) . '">' . htmlspecialchars($n['nombre']) . '</option>';
                }
                $html .= '</select>';
                $html .= '</div></div>';
                $html .= '<input type="hidden" name="vars[' . $nombre . ']" id="final_' . $nombre . '">';
                $html .= '<div class="form-text small text-muted mt-1">Seleccione cargo y funcionario. Para casos de EXTERIOR, deje ambos vacíos.</div>';
                break;
                
            case 'lugar':
                global $conn;
                $html .= '<select name="vars[' . $nombre . ']" class="form-select select2-init" ' . $requerido . '>';
                $html .= '<option value="">-- Seleccione Municipio/Distrito --</option>';
                $municipios = $conn->query("SELECT municipio FROM municipios ORDER BY municipio ASC");
                while ($m = $municipios->fetch_assoc()) {
                    $selected = ($valor_anterior == $m['municipio']) ? 'selected' : '';
                    $html .= '<option value="' . htmlspecialchars($m['municipio']) . '" ' . $selected . '>' . htmlspecialchars($m['municipio']) . '</option>';
                }
                $html .= '</select>';
                break;
                
            case 'referencia_legal':
                $html .= self::generarCampoReferenciaLegal($nombre, $valor_anterior);
                break;
                
            default: // text
                $html .= '<input type="text" name="vars[' . $nombre . ']" class="form-control" ' . $requerido . ' value="' . htmlspecialchars($valor_anterior) . '">';
                break;
        }
        
        $html .= $ayuda;
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Genera campo especial para referencia legal (Escritura/Acta/Partida)
     */
    private static function generarCampoReferenciaLegal($nombre, $valor_anterior = '') {
        global $conn;
        
        $html = '<div class="p-2 border rounded bg-light">';
        $html .= '<select class="form-select select2-init mb-2 tipo-ref" data-nombre="' . $nombre . '">';
        $html .= '<option value="">-- Tipo de Documento --</option>';
        $html .= '<option value="escritura">Escritura Pública</option>';
        $html .= '<option value="acta">Acta Matrimonial</option>';
        $html .= '<option value="partida">Partida (Distrito Externo)</option>';
        $html .= '</select>';
        
        // Campos para escritura/acta
        $html .= '<div class="campos-doc" style="display:none;">';
        $html .= '<input type="text" class="form-control ref-numero" placeholder="Número de documento">';
        $html .= '</div>';
        
        // Campos para partida externa
        $html .= '<div class="campos-partida-ext row g-2" style="display:none;">';
        $html .= '<div class="col-md-3"><input type="text" class="form-control ref-anio" placeholder="Año"></div>';
        $html .= '<div class="col-md-3"><input type="text" class="form-control ref-libro" placeholder="Libro"></div>';
        $html .= '<div class="col-md-3"><input type="text" class="form-control ref-asiento" placeholder="Asiento"></div>';
        $html .= '<div class="col-md-3"><input type="text" class="form-control ref-folio" placeholder="Folio"></div>';
        $html .= '<div class="col-md-3"><input type="text" class="form-control ref-tomo" placeholder="Tomo"></div>';
        $html .= '<div class="col-md-6">';
        $html .= '<select class="form-select select2-init ref-distrito">';
        $html .= '<option value="">-- Municipio/Distrito --</option>';
        
        $municipios = $conn->query("SELECT municipio FROM municipios ORDER BY municipio ASC");
        while ($m = $municipios->fetch_assoc()) {
            $html .= '<option value="' . htmlspecialchars($m['municipio']) . '">' . htmlspecialchars($m['municipio']) . '</option>';
        }
        $html .= '</select>';
        $html .= '</div></div>';
        
        $html .= '<input type="hidden" name="vars[' . $nombre . ']" class="ref-final" value="' . htmlspecialchars($valor_anterior) . '">';
        $html .= '</div>';
        
        return $html;
    }
}
?>