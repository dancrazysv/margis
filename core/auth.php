<?php
class Auth {
    public static function puedeEditar($registro, $usuario_tipo) {
        // El administrador siempre puede editar
        if ($usuario_tipo === 'ADMIN') return true; 
        
        // El usuario solo si está ABIERTO o DEVUELTO
        $estados_permitidos = ['ABIERTO', 'DEVUELTO'];
        return in_array($registro['estado'], $estados_permitidos);
    }
}
?>