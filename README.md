# SIBW
Proyecto de la asignatura Sistemas de Información Basados en Web (UGR)

La web cuenta con 5 tipos de usuarios:

1.- Usuarios no registrados --> Solo pueden buscar eventos, así como ver estos y su comentarios, pero no podrán escribir nada. Pueden
crearse un usuario siempre y cuando el nombre que escojan no esté ya como el nombre de otro usuario.

2.- Usuario: usuario // Contraseña: usuario --> Usuario básico. Puede hacer lo mismo que el usuario no registrado, pero añadiendo la funcionalidad de poder comentar
en los eventos, así como poder modificar sus datos personales (nombre, correo y contraseña) dentro de los limites permitidos (no se permite repetición de nombre
entre usuarios).

3.- Usuario: moderador // Contraseña: moderador --> Tiene las mismas funciones que un usuario básico, pero añadiendo la posibilidad de buscar y listar los comentarios
realizados en todos los eventos, así como modificarlos y eliminarlos.

4.- Usuario: gestor // Contraseña: gestor --> Añade a las funcionalidades del moderador las de crear, editar o borrar eventos de la página.

5.- Usuario: super // Contraseña: super --> Administradores de la web. Possen las funcionalidades de gestor más la posibilidad de listar a los usuarios 
existentes en la web, asi como cambiar su rol y eliminarlos (un superusuario no puede eliminarse ni cambiar su rol a sí mismo; del mismo modo, tampoco puede
eliminar a otros usuarios que tengan también el rol de "superusuario". Para poder hacerlo, antes debe cambiar el rol de estos a cualquier otro de los existentes).

Se ha empleado el sistemas de plantillas Twig, pero para elaborar la practica este fue usado a traves de Docker, por lo que para que todo funcione correctamente 
deberemos cambiar la dirección del "require_once" del principio de todos los php a la direccion donde tengamos el fichero "autoload.php" de Twig.
