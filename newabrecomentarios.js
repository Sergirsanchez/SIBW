var censoredWords = [];

/*-------------------------INTRODUCIRLE VALORES A palabrasCensuradas-------------------------*/
function enterWords(word){
    censoredWords.push(word);
}

/*-------------------------FUNCIÓN PARA ABRIR LA CAJA DE COMENTARIOS -------------------*/
function openComments(){
  
    var elements = document.getElementsByClassName("commentMenu");
    elements[0].style.width = "50%";
    document.getElementById("open").style.display = "none";

    
}

/*-----------------------FUNCIÓN PARA CERRAR LA CAJA DE COMENTARIOS----------------------*/
function closeComments(){
    var elements = document.getElementsByClassName("commentMenu");
    elements[0].style.width = "0%";
    document.getElementById("open").style.display = "block"; 
}

/*------------FUNCIÓN PARA CAMBIAR LAS PALABRAS NEGATIVAS POR ASTERISCOS---------------------*/
function addStars(){

    /*Las palabras som: "malo","feo","horroroso" y "pesimo"*/
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

/*----------------------- FUNCIÓN PARA LA BÚSQUEDA DE COMENTARIOS ------------------*/
$(document).ready(function(){
    var onlyPublished = false

   $('#SearchEventName').focus() 

   $('#SearchEventName').on('keyup',function(){
      var search = $('#SearchEventName').val()
      
      if($('#noPublishedCheckBox').prop('checked')){    /*Si está marcado, añadimos los eventos no publicados también*/
        onlyPublished = false;
        console.log("THE VALUE IS: "+onlyPublished);
      }

      else{
          onlyPublished = true;
        console.log("THE VALUE IS: "+onlyPublished);

      }

      $.ajax({
         type: 'POST',
         url: 'busquedaeventoAJAX.php',
         data :{'lookForEvent': search,'onlyPublished': onlyPublished},
      })
      .done(function(result){   /*Cuando obtengamos la respuesta la procesamos*/
        processResult(result,search)
      })
      .fail(function(){
          alert("Hubo un error")
      })
   })

})

    /*------------------------- Creamos la estructura de los comentarios -----------------------------*/
    function processResult(result,textToSearch){
        resultEvents = "";
        fotosEventos = ""; /*Almacenamos todas las fotos en el formato necesitado*/
        etiquetasEventos = "";/*Almacenamos todas las etiquetas en el formato necesitado*/
        numberOfIndexes = [];/*Almacenamos los indices de todas las coincidencias*/
        currentIndex = 0;
        finalEventName = "";

        if(result['textoEventos'].length == 0){   /*No hay coincidencias*/
            resultEvents += "<p class='noCoincidenceAJAX'> No hay coincidencias. </p>"
        }


        else{
            for (var i = 0; i < result['textoEventos'].length; i++){

                /*Vamos a ver donde coincide con la búsqueda hecha*/
    
                /*Primero, obtenemos para cada eventos los índices de cada una de las coincidencias*/
                nameAux = result['textoEventos'][i]['titulo'];  /*Guardamos en una variable auxiliar*/
    
                nameAux = nameAux.toLowerCase();                /*Convertimos en minusculas todo*/
                textToSearch = textToSearch.toLowerCase();
    
                var index = nameAux.indexOf(textToSearch);
                currentIndex = index;
                numberOfIndexes.push(currentIndex);
                
    
                if(textToSearch != ""){
                    while(index != -1){   /*Mientras tengamos un índice*/
                        nameAux = nameAux.slice(index+1,(nameAux.length)-1);
                        index = nameAux.indexOf(textToSearch);
    
                        if(index != -1){
                            currentIndex = currentIndex + index + 1;
                            numberOfIndexes.push(currentIndex);
                        }
    
                    }
    
                    /*Una vez tenemos todos los indices de dicho evento en un array, nos disponemos a cambiarles el formato*/
    
                    finalEventName = result['textoEventos'][i]['titulo'];
    
    
                    for(var j = 0; j < numberOfIndexes.length; j++){
                        
    
                        if(j == 0){
                            finalEventName = finalEventName.replace(finalEventName.substring(numberOfIndexes[j],(numberOfIndexes[j]+(textToSearch.length))),
                                        '<span id="remark">'+finalEventName.substring(numberOfIndexes[j],(numberOfIndexes[j]+(textToSearch.length)))+'</span>');
    
                        }
    
                        else{
                            var formula = ( numberOfIndexes[j] + ((j*25)) + (j*textToSearch.length) ) - j;/*Heurística*/
    
    
                            if( textToSearch.length == 1){
                                var prueba = finalEventName.substring(formula);
                                var newString = "<span id='remark'>"+finalEventName[formula]+"</span>";
                                finalEventName = finalEventName.substring(0,formula)+newString+finalEventName.substring((formula+1),finalEventName.length);
    
                            }
    
                            else{   /*NO está bien implementado del todo*/
                                var prueba = finalEventName.substring(formula,(formula+textToSearch.length));
                                var newString = "<span id='remark'>"+finalEventName.substring(formula-j,(formula+textToSearch.length)-j)+"</span>";
                                finalEventName = finalEventName.substring(0,formula-j)+newString+finalEventName.substring((formula+textToSearch.length-j),finalEventName.length);
    
                            }
    
                        }
    
                    }
                    /*Por último, reestablecemos valores para el siguiente evento*/
                    currentIndex = 0;
                    numberOfIndexes = [];
                }
                else{
                    currentIndex = 0;
                    finalEventName = result['textoEventos'][i]['titulo'];
                }
    
    
               resultEvents +=' <div class="result">'+
    
                                    '<div class ="secondLeft">'+
    
                                        '<p class="idEvent">Evento '+(i+1)+':</p>'+
    
                                        '<a class ="linkEvents" href="evento.php?nameEvent='+result['textoEventos'][i]['titulo']+'" title="'+result['textoEventos'][i]['titulo']+'">'
                                        +finalEventName+'</a>'+
    
                                    '</div>'+
                        '</div>';
    
                fotosEventos = "";
                etiquetasEventos = "";
            }
    
        }

        $('#resultAJAX').html(resultEvents);
        
    }