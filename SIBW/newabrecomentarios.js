var censoredWords = [];

/*-------------------------INTRODUCIRLE VALORES A palabrasCensuradas-------------------------*/
function enterWords(word){
    censoredWords.push(word);
}

/*-------------------------METODOS PARA ABRIR EL BOCADILLO DE COMENTARIOS -------------------*/
function openComments(){

    /*-----------AUNQUE DIJIMOS QUE CREARLO TODO DESDE JS ERA MAS ENRREVESADO, DEJO EL CÓDIGO COMENTADO POR IS SE PEUDE REUTILIZAR EN UN FUTURO-----------
    if(document.getElementsByClassName("preDesignedCommentsContainer")[0].hasChildNodes() == false){
        var nombres = document.getElementsByClassName("nombres");
        var fechas = document.getElementsByClassName("fechas");
        var contenidos = document.getElementsByClassName("contenidos");
        var correos = document.getElementsByClassName("correos");

        for (var i = 0; i < nombres.length; i++){
            createComment(nombres[i].innerText, fechas[i].innerText, contenidos[i].innerText, correos[i].innerText);
        }

    }
     */   
    var elements = document.getElementsByClassName("commentMenu");
    elements[0].style.width = "50%";
    document.getElementById("open").style.display = "none";

    
}

/*-----------------------METODOS PARA CERRAR LA CAJA DE COMENTARIOS----------------------*/
function closeComments(){
    var elements = document.getElementsByClassName("commentMenu");
    elements[0].style.width = "0%";
    document.getElementById("open").style.display = "block"; 
}

/*-----------------------METODOS PARA AÑADIR NUEVOS COMENTARIOS ----------------------------*/
function sendComment(){
    var name = document.getElementById("nameInput").value;
    var email = document.getElementById("emailInput").value;
    var text = document.getElementById("commentInput").value;
    var date = createDate();
    var OK = checkFields(name,email,text);

    if(OK == true){
        createComment(name,date,text,email);
    }
   
    
}
/*------------METODOS PARA CAMBIAR LAS PALABRAS NEGATIVAS POR ASTERISCOS---------------------*/
function addStars(){

    console.log("THE SIZE IS:");
    console.log(censoredWords.length);

    var currentWord = "";
    var content = document.getElementById("commentInput").value;
    var star = "*";
    var starWord=" ";

    /*Miramos la lista de las palabras censuradas y si encontramos alguna en el texto del comentario:
        1.-Generamos tantos asteriscos como letras tenga la palabra
        2.-Reemplazamos la palabra censurada que hemos detectado por el string compuesto de asteriscos de la misma longitud que la palabra
    */

    for (var i = 0; i < censoredWords.length; i++){
        if(content.match(censoredWords[i]) == censoredWords[i]){
            currentWord = censoredWords[i];

            for (var i = 0; i < currentWord.length; i++){
                starWord += star;
            }
            content = content.replace(currentWord,starWord);
            document.getElementById("commentInput").value = content;
        }
    }

}

/*--------------------------------CREAR COMENTARIOS------------------------*/

function createComment(name,date,text,email){
    var newComment = document.createElement("div");
    var newName = document.createElement("p");
    var newDate = document.createElement("p");
    var newCommentContent = document.createElement("p");
    var OK = false;
    
    newComment.className = "preDesignedComment";

    
    var name = name;
    var email = email;
    var text = text;
    var date = date;

    newName.innerText = name;
    newDate.innerText = date;
    newCommentContent.innerText = text;

    newComment.appendChild(newName);
    newComment.appendChild(newDate);
    newComment.appendChild(newCommentContent);
    document.getElementsByClassName("preDesignedCommentsContainer")[0].appendChild(newComment);
}

/*------------------------CREAR Y DAR FORMATO A LA FECHA Y HORA DEL NUEVO MENSAJE-----------------*/
function createDate(){
    var date = new Date();
 
    
    var current_date = date.getDate()+"/"+(date.getUTCMonth()+1)+"/"+date.getFullYear();
    var current_time = date.getHours()+":"+date.getMinutes();
    
    /*Condicionales para que, si por ejemplo, publicamos a las 00.01, ponga bien la hora y no aparezca como "0.1"*/
    if(date.getHours().toString().length == 1){
        current_time = "0"+date.getHours()+":"+date.getMinutes();
    }

    if(date.getMinutes().toString().length == 1){
        current_time = date.getHours()+":0"+date.getMinutes();
    }

    current_date += " "+current_time;

    return current_date;
}


/*------------------------------------CHECKEAR QUE TODO VA OK------------------------------------*/
function checkFields(name,email,text){
    var OK = true;

    if( name == "" | email == "" | text == ""){   /*Garantizar que todos los campos están rellenos*/
        alert("Por favor, rellena todos los campos");
        OK = false;
    }

    else if((email.match(/@correo.ugr.es/) !="@correo.ugr.es") && (email.match(/@ugr.es/) !="@ugr.es")){    /*Garantizamos que solo pueden introducirse correo que sean  "@correo.ugr.es"
                                                                                                            o "@ugr.es"*/
        alert("El correo introducido no es correcto. Por favor introduzca un correo válido")
        OK = false;
    }

    return OK;
}

/*----------------------------------ELIMINAR EL TEXTO DEL TEXTAREA--------------------------------*/
function deleteText(element){
    element.value=" ";
}

/*--------------------------------CREAR LAS FOTOS DE LA GALERIA-------------------------------
function createPhotos(){
    var srcPhotos = document.getElementsByClassName("srcFotos");
    var titlePhotos = document.getElementsByClassName("titleFotos");

    /*Vemos si hay una foto o mas, para crear los li's correspondientes
    
    photosStructure(0,srcPhotos[0].innerText,titlePhotos[0].innerText,true);
    
    if(srcPhotos.length > 1){
        for(var i = 1; i < srcPhotos.length; i++){
            photosStructure(i,srcPhotos[i].innerText,titlePhotos[i].innerText,false); 
        }
    }
}

------------------------------ESTRUCTURA DE LAS FOTOS------------------------------
function photosStructure(id,src,title,checked){
    var finalElement = document.createElement("li");
    var inputElement = document.createElement("input");
    var labelElement = document.createElement("label");
    var imgElement = document.createElement("img");


    /*Damos formato al input
    inputElement.type = "radio";
    inputElement.id = "button"+id.toString();
    inputElement.name = "radioMenu";
    inputElement.checked = checked;

    /*Damos formato al label
    labelElement.htmlFor = inputElement.id;

    /*Damos formato a la imagen
    imgElement.src = src;
    imgElement.title = title;

    finalElement.appendChild(inputElement);
    finalElement.appendChild(labelElement);
    finalElement.appendChild(imgElement);
    document.getElementById("slider").appendChild(finalElement);

}
*/