window.addEventListener("load", function () {
    let form = document.getElementById("theForm");
    form.addEventListener("submit", function (event) {
        event.preventDefault();
        requestAndSetPasswordAndEntropy();
    });
    document.getElementById('generated').style.display = 'none';
});

function isEmptyObject(obj) {
    // This works for arrays too.
    for (let name in obj) {
        return false
    }
    return true
}

function requestAndSetPasswordAndEntropy() {
    'use strict';
    // Should filter input before send (client input validation)
    let xmlhttp, AJAXresponse, parameters,
        allcaps = document.getElementById('allcaps').checked ? 'true' : 'false',
        capitalize = document.getElementById('capitalize').checked ? 'true' : 'false',
        punctuate = document.getElementById('punctuate').checked ? 'true' : 'false',
        addslashes = document.getElementById('addslashes').checked ? 'true' : 'false',
        minWordSize = document.getElementById('minWordSize').value,
        separators = document.getElementById('separators').value,
        level = document.getElementById('level').value,
        passwordField;

    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        //IE6, IE5
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
            AJAXresponse = JSON.parse(xmlhttp.responseText);
            passwordField = document.getElementById('password');
            passwordField.value = AJAXresponse.password;
            document.getElementById('entropy').innerText = AJAXresponse.entropy;
            document.getElementById('generated').style.display = 'block';
            if (!isEmptyObject(AJAXresponse.error)) console.log(AJAXresponse.error);
        }
    };
    // Should filter input before send (client-side input validation)
    xmlhttp.open('POST', 'passgenController.php', true);
    parameters = 'allcaps=' + encodeURIComponent(allcaps);
    parameters += "&capitalize=" + encodeURIComponent(capitalize);
    parameters += "&punctuate=" + encodeURIComponent(punctuate);
    parameters += "&addslashes=" + encodeURIComponent(addslashes);
    parameters += "&separators=" + encodeURIComponent(separators);
    parameters += "&minWordSize=" + encodeURIComponent(minWordSize);
    parameters += "&level=" + encodeURIComponent(level);
    xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xmlhttp.send(parameters);
    return false;
}
