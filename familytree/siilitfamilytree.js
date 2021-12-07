const findButton = document.getElementById("find-tree");
const textField = document.getElementById("siili-search");

textField.addEventListener("keyup", function(e) {
    if(e.key == 'Enter'){
        if(textField.value.length == 0){
            warnings.innerHTML = "Kirjoita ensin ID";
            warnings.style.cssText = "color:red;";
            return null;
        }
        textField.blur();
        siilit_findFamilyHistory(textField.value);
    }
})

jQuery("#find-tree").click(function(e) {
    e.preventDefault();
    if(textField.value.length == 0){
        warnings.innerHTML = "Kirjoita ensin ID";
        warnings.style.cssText = "color:red;";
        return null;
    }
    siilit_findFamilyHistory(textField.value);
})


//fill unknown parents
function siilit_checkArrayContents(data){
    if(!data){
        data = [{Kasvattaja: ' ', Nimi: 'Ei tiedossa', Siilinro: ' '}];
    } else if(!data[0].Nimi){
        let temp = data;
        data = [{Kasvattaja: ' ', Nimi: temp, Siilinro: ' '}];
    }
    return data;
}


function siilit_showTree(data){
    let warning = document.getElementById("warnings");
    console.log('wanted: ' + data.wanted[0]);
    if(!data.wanted[0]){
        warning.innerHTML = "Siiliä ei löytynyt. Tarkista tiedot."
        return null;
    }
    warning.innerHTML = "";

    let ilnessesWarning = document.createElement("h3");
    ilnessesWarning.innerHTML = "Suvusta löytyy merkintöjä:<br>";
    ilnessesWarning.id = "ilnesses-warning";
    let ilnesses = false;
    //iterating through data for hedgehogs without IDs and checking familyilnesses
    for(const item in data){
        //console.log(data[item]);
        data[item] = siilit_checkArrayContents(data[item]);
        
        if(data[item][0].Kuolinsyy){
            ilnessesWarning.innerHTML += data[item][0].Nimi + ', ' + data[item][0].Kuolinsyy + '<br>';
            ilnesses = true;
        }
    }

    siilit_createTable(data);
    if(ilnesses){
        treecontainer.appendChild(ilnessesWarning);
    }
    siilit_checkFamilyHistory(data);
}

function siilit_checkFamilyHistory(data){
    let siblings = fatherdaughter = motherson = family = false;
    //parents: father-daughter or mother-son ?

    if((data.mumsDad[0].Nimi != "Ei tiedossa")  && (data.mumsDad[0].Nimi === data.dad[0].Nimi)){
        fatherdaughter = true;
    } else if((data.dadsMum[0].Nimi != "Ei tiedossa") && (data.dadsMum[0].Nimi === data.mum[0].Nimi)){
        motherson = true;
    }

    //parents have one or two same parent(s)
    if((data.dadsDad[0].Nimi != "Ei tiedossa") && (data.dadsDad[0].Nimi === data.mumsDad[0].Nimi)){
        siblings = true;
    } else if((data.dadsMum[0].Nimi != "Ei tiedossa") && (data.dadsMum[0].Nimi === data.mumsMum[0].Nimi)){
        siblings = true;
    }

    //parents' parents and their parents' parents
    if((data.dadsDadsDad[0].Nimi != "Ei tiedossa") && (data.dadsDadsDad[0].Nimi === data.mumsDad[0].Nimi)){
        family = true;
    } else if((data.dadsDadsMum[0].Nimi != "Ei tiedossa") && (data.dadsDadsMum[0].Nimi === data.mumsMum[0].Nimi)){
        family = true;
    } else if((data.dadsMumsDad[0].Nimi != "Ei tiedossa") && (data.dadsMumsDad[0].Nimi === data.mumsDad[0].Nimi)){
        family = true;
    } else if((data.dadsMumsMum[0].Nimi != "Ei tiedossa") && (data.dadsMumsMum[0].Nimi === data.mumsMum[0].Nimi)){
        family = true;
    } else if((data.mumsDadsDad[0].Nimi != "Ei tiedossa") && (data.mumsDadsDad[0].Nimi === data.dadsDad[0].Nimi)){
        family = true;
    } else if((data.mumsDadsMum[0].Nimi != "Ei tiedossa") && (data.mumsDadsMum[0].Nimi === data.dadsMum[0].Nimi)){
        family = true;
    } else if((data.mumsMumsDad[0].Nimi != "Ei tiedossa") && (data.mumsMumsDad[0].Nimi === data.dadsDad[0].Nimi)){
        family = true;
    } else if((data.mumsMumsMum[0].Nimi != "Ei tiedossa") && (data.mumsMumsMum[0].Nimi === data.dadsMum[0].Nimi)){
        family = true;
    }

    let treecontainer = document.getElementById("treecontainer");
    if(motherson){
        let motherSonWarning = document.createElement("h3");
        motherSonWarning.innerHTML = "Vanhemmat ovat emo ja poika.<br>";
        motherSonWarning.className = "warning";
        treecontainer.appendChild(motherSonWarning);
    }
    if(fatherdaughter){
        let fatherDaughterWarning = document.createElement("h3");
        fatherDaughterWarning.innerHTML = "Vanhemmat ovat isä ja tytär.<br>";
        fatherDaughterWarning.className = "warning";
        treecontainer.appendChild(fatherDaughterWarning);
    }
    if(siblings){
        let siblingWarning = document.createElement("h3");
        siblingWarning.innerHTML = "Vanhemmilla on sama emo ja/tai isä.<br>";
        siblingWarning.className = "warning";
        treecontainer.appendChild(siblingWarning);
    }
    if(family){
        let familyWarning = document.createElement("h3");
        familyWarning.innerHTML = "Toisen vanhemman vanhempi on toisen vanhemman isovanhempi.<br>";
        familyWarning.className = "warning";
        treecontainer.appendChild(familyWarning);
    }
}


function siilit_createTable(data){
    let tree = document.createElement("div");
    tree.id = "tree";
    tree.className = "familytree";
    let header = document.createElement('h2');
    header.innerHTML = "Siilin " + data.wanted[0].Nimi + ' sukupuu';
    header.id = "tree-header";
    document.getElementById("treecontainer").appendChild(header);
    
    let root = document.createElement("ul");
    let childLI = document.createElement("li");
    let childNode = siilit_createNode(data.wanted[0]);
    childLI.appendChild(childNode);
    root.appendChild(childLI);
    
    
    let parents = document.createElement("ul");
    let dadLI = document.createElement("li");
    let dadNode = siilit_createNode(data.dad[0]);
    dadLI.appendChild(dadNode);
    let mumLI = document.createElement("li");
    let mumNode = siilit_createNode(data.mum[0]);
    mumLI.appendChild(mumNode);
    parents.appendChild(dadLI);
    parents.appendChild(mumLI);
    childLI.appendChild(parents);


    let dadsParents = document.createElement("ul");
    let dadsDadLI = document.createElement("li");
    let dadsMumLI = document.createElement("li");
    let dadsDadNode = siilit_createNode(data.dadsDad[0]);
    let dadsMumNode = siilit_createNode(data.dadsMum[0]);
    dadsDadLI.appendChild(dadsDadNode);
    dadsMumLI.appendChild(dadsMumNode);
    dadsParents.appendChild(dadsDadLI);
    dadsParents.appendChild(dadsMumLI);
    dadLI.appendChild(dadsParents);

    let mumsParents = document.createElement("ul");
    let mumsDadLI = document.createElement("li");
    let mumsMumLI = document.createElement("li");
    let mumsDadNode = siilit_createNode(data.mumsDad[0]);
    let mumsMumNode = siilit_createNode(data.mumsMum[0]);
    mumsDadLI.appendChild(mumsDadNode);
    mumsMumLI.appendChild(mumsMumNode);
    mumsParents.appendChild(mumsDadLI);
    mumsParents.appendChild(mumsMumLI);
    mumLI.appendChild(mumsParents);
    

    let dadsDadsParents = document.createElement("ul");
    let dadsDadsDadLI = document.createElement("li");
    let dadsDadsMumLI = document.createElement("li");
    let dadsDadsDadNode = siilit_createNode(data.dadsDadsDad[0]);
    let dadsDadsMumNode = siilit_createNode(data.dadsDadsMum[0]);
    dadsDadsDadLI.appendChild(dadsDadsDadNode);
    dadsDadsMumLI.appendChild(dadsDadsMumNode);
    dadsDadsParents.appendChild(dadsDadsDadLI);
    dadsDadsParents.appendChild(dadsDadsMumLI);
    dadsDadLI.appendChild(dadsDadsParents);

    let dadsMumsParents = document.createElement("ul");
    let dadsMumsDadLI = document.createElement("li");
    let dadsMumsMumLI = document.createElement("li");
    let dadsMumsDadNode = siilit_createNode(data.dadsMumsDad[0]);
    let dadsMumsMumNode = siilit_createNode(data.dadsMumsMum[0]);
    dadsMumsDadLI.appendChild(dadsMumsDadNode);
    dadsMumsMumLI.appendChild(dadsMumsMumNode);
    dadsMumsParents.appendChild(dadsMumsDadLI);
    dadsMumsParents.appendChild(dadsMumsMumLI);
    dadsMumLI.appendChild(dadsMumsParents);


    let mumsDadsParents = document.createElement("ul");
    let mumsDadsDadLI = document.createElement("li");
    let mumsDadsMumLI = document.createElement("li");
    let mumsDadsDadNode = siilit_createNode(data.mumsDadsDad[0]);
    let mumsDadsMumNode = siilit_createNode(data.mumsDadsMum[0]);
    mumsDadsDadLI.appendChild(mumsDadsDadNode);
    mumsDadsMumLI.appendChild(mumsDadsMumNode);
    mumsDadsParents.appendChild(mumsDadsDadLI);
    mumsDadsParents.appendChild(mumsDadsMumLI);
    mumsDadLI.appendChild(mumsDadsParents);

    let mumsMumsParents = document.createElement("ul");
    let mumsMumsDadLI = document.createElement("li");
    let mumsMumsMumLI = document.createElement("li");
    let mumsMumsDadNode = siilit_createNode(data.mumsMumsDad[0]);
    let mumsMumsMumNode = siilit_createNode(data.mumsMumsMum[0]);
    mumsMumsDadLI.appendChild(mumsMumsDadNode);
    mumsMumsMumLI.appendChild(mumsMumsMumNode);
    mumsMumsParents.appendChild(mumsMumsDadLI);
    mumsMumsParents.appendChild(mumsMumsMumLI);
    mumsMumLI.appendChild(mumsMumsParents);

    tree.appendChild(root);
    document.getElementById("treecontainer").appendChild(tree);
}

function siilit_createNode(hedgehog){
    let node = document.createElement("p");
    node.id = hedgehog.Siilinro;
    node.innerHTML = hedgehog.Kasvattaja + "<br><b>" + hedgehog.Nimi + "</b><br>" + hedgehog.Siilinro + "<br>";
    node.onclick = function () {
        siilit_findFamilyHistory(hedgehog.Nimi);
        textField.value = hedgehog.Nimi;
    }
    return node;
}



function siilit_clearTree(){
    let familytree = document.getElementById('tree');
    if(familytree){
        familytree.parentNode.removeChild(familytree);
    }

    let warnings = document.getElementsByTagName("h3");
    while(warnings[0]){
        warnings[0].parentNode.removeChild(warnings[0]);
    }

    let treeHeader = document.getElementById("tree-header");
    if(treeHeader){
        treeHeader.parentNode.removeChild(treeHeader);
    }
}


function siilit_findFamilyHistory(wanted){
    jQuery.ajax({
        type: "POST",
        dataType: "json",
        url: siilit_ajax_obj.ajax_url,
        data: {action: "ajax_siilit", name: wanted, _ajax_nonce: siilit_ajax_obj._ajax_nonce},
        success: function(response){
            siilit_clearTree();
            siilit_showTree(response.data);
        },
        error: function(){
            let errorMessage = document.createElement("h3");
            errorMessage.innerHTML = "Tapahtui virhe. Yritä uudestaan.";
            errorMessage.id = "error-message";
            treecontainer.appendChild(familyWarning);
        }
    }).done()
}


