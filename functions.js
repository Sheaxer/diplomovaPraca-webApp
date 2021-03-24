var selectedImages = 0;

$(document).ready(function()
{

    let selectedImages =0;
    //$("#addNomenklatorAddUrl").hide();

    /*if(localStorage.getItem("uploadedImageLinks") === null)
    {
        localStorage.setItem("uploadedImageLinks", JSON.stringify([]));
    }*/
    let loginForm = $("#loginForm");
    let uploadForm = $("#imageUploadForm");
    let logOffButton = $("#logOff");
    if((localStorage.getItem("token") !== null) && (localStorage.getItem("expiresAt")!== null))
    {

        let expiresAt = new Date(localStorage.getItem("expiresAt"));
       // console.log(expiresAt);
        if(expiresAt <= new Date()) // token is expired
        {
            localStorage.removeItem("token");
            localStorage.removeItem("expiresAt");
            console.log("tokenExpired");
            loginForm.show();
            logOffButton.hide();
        }
        else
        {
            loginForm.hide();
            logOffButton.show();
            /*console.log(localStorage.getItem("token"));
            console.log(localStorage.getItem("expiresAt"));*/
        }

    }
    else
    {

    }
    logOffButton.click(function (e)
    {
        e.preventDefault();
        localStorage.removeItem("token");
        localStorage.removeItem("expiresAt");
        loginForm.show();
    })
   loginForm.submit(function(e)
   {
       e.preventDefault();
        let data = {};
        data.username = $("#usernameInput").val();
        data.password = $("#passwordInput").val();
        $.ajax(
            {
                url: "login",
                type: "post",
                contentType: "application/json",
                dataType:"json",
                data: JSON.stringify(data),
                success: function(data)
                {
                    localStorage.setItem("token", data.token);
                    localStorage.setItem("expiresAt",data.expiresAt);
                   loginForm.hide();
                },
                error: function (request, status, error) {
                    console.log(request.responseText);
                }
            }
        );
   })

    uploadForm.submit(function (e)
    {
      e.preventDefault();
        $.ajax( {
            beforeSend: function(request) {
                request.setRequestHeader("authorization", localStorage.getItem("token"));
            },
            url: 'images.php',
            type: 'POST',
            enctype: 'multipart/form-data',
            data: new FormData( this ),
            processData: false,
            contentType: false,
            cache : false,
            success: function (data)
            {
              /*console.log(data);

              var links = JSON.parse(localStorage.getItem("uploadedImageLinks"));

              console.log(links);
              links.push(data.url);
              localStorage.setItem("uploadedImageLinks",JSON.stringify(links));*/
              //links.push(data)

            },
            error: function (request, status, error) {
                console.log(request.responseText);
            }
        } );
    });

    let nomenklatorUploadForm = $("#nomenklatorUploadForm");

    getNomenklatorFolders();

    $("#addNomenklatorAddUrl").click(function (e)
    {
       e.preventDefault();
        //addUrlInput($("#addNomenklatorImages"));
        addNewNomenclatorImage($("#addNomenklatorImages"));
       console.log("clicked button");

    });

    nomenklatorUploadForm.submit(function (e)
    {
        console.log("I am here");
        e.preventDefault();
        var formData = new FormData();
        /*$("input[name='nomenclatorImage']").each(function (index)
        {
            if($(this)[0].files.length > 0)
            {
                formData.append("nomenclatorImage",$(this)[0].files[0]);
                console.log("Appending");
            }

           //console.log($(this)[0].files[0]);
        });*/
        //console.log(formData);
        let nomenklator ={};
        nomenklator.nomenclatorImages = [];
        nomenklator.signature = $("#signatureInput").val();
        let selectedFolder = $("#addNomenklatorFolderSelect option:selected").val();
        if(selectedFolder !== "")
            nomenklator.folder = selectedFolder;
        nomenklator.language = $("#addNomenklatorLanguage").val();
        console.log(nomenklator.language);
        let nomenclatorKeyUsers = [];

        nomenklator.completeStructure = $("#addNomenklatorCompleteStructure").val();

        $("#addNomenklatorKeyUserSelect option:selected").each(function (index)
        {
            let tmpKeyUser ={};
            tmpKeyUser.id = parseInt($(this).val());
           nomenclatorKeyUsers.push(tmpKeyUser);
        });

        if(nomenclatorKeyUsers.length !== 0)
            nomenklator.keyUsers = nomenclatorKeyUsers;

        $("div.addNomenclatorImageDiv").each(function ()
        {
            let nomenclatorImage = {};
            nomenclatorImage.structure = $(this).find("textarea").val();
            //console.log($(this).find("textarea").val());
            if($(this).find("input[name='isLocal']").prop("checked")) // URL
            {
                nomenclatorImage.isLocal = false;
                nomenclatorImage.url = $(this).find("input[name='url']").val();
                //console.log("Checked");
                //nomenklator.structures
            }
            else // UPLOAD IMAGE
            {
                nomenclatorImage.isLocal = true;
                formData.append("nomenclatorImage[]",$(this).find("input[name='nomenclatorImage']")[0].files[0]);
                //console.log("Unchecked");
            }
            nomenklator.nomenclatorImages.push(nomenclatorImage);
            console.log(nomenclatorImage);
        })
        console.log(nomenklator);
        formData.append("data",JSON.stringify(nomenklator));
        for (var pair of formData.entries()) {
            console.log(pair[0]+ ', ' + pair[1]);
        }

        $.ajax({
            beforeSend: function(request) {
                request.setRequestHeader("authorization", localStorage.getItem("token"));
            },
            url: 'nomenclatorKeys',
            type: 'POST',
            enctype: 'multipart/form-data',
            data: formData,
            processData: false,
            contentType: false,
            cache : false,
            success: function (data)
            {
                console.log(data);

            },
            error: function (request, status, error) {
                console.log(request.responseText);
            }
        });


        /*$("textarea[name='structure']").each(function (index)
            {
                nomenklator.structures.push($(this).val());
            }
        );*/

       //
    });


    addNewNomenclatorImage($("#addNomenklatorImages"));
    getNomenclatorKeyUsers();
    //console.log("hello");

   /* $("#addNomenklatorSlider").click(function (e)
    {
        $("#addNomenklatorImages").empty();
        if($(this).prop("checked"))
        {
            $("#addNomenklatorAddUrl").show();
            addUrlInput($("#addNomenklatorImages"));
        }
        else
        {
            $("#addNomenklatorAddUrl").hide();
          addUnasignedImages($("#addNomenklatorImages"));

        }
    })*/

});

function getNomenklatorFolders() {
    console.log("nomenclatorFolders");
    $.ajax(
        {
            url: "folders",
            type: "get",
            success: function (data)
            {
                if(data != null)
                    fillFolders(data);
                console.log(data);
            },
            error: function (request, status, error) {
                console.log(request.responseText);
            }
        }
    );

}

function getNomenclatorKeyUsers(){
    $.ajax(
        {
            url: "keyUsers",
            type: "get",
            success: function (data)
            {
                console.log("Got Data");
                if(data != null)
                {
                    fillKeyUsers(data)
                }

            },
            error: function (request, status, error) {
                console.log(request.responseText);
            }
        }
    );
}

function fillFolders(folders)
{
    if(folders == null)
        return;
    $("#addNomenklatorFolderSelect").find('option').remove();
    let tmp = $("<option></option>")
    tmp.val("");
    tmp.text("No folder");
    $("#addNomenklatorFolderSelect").append(tmp);
    folders.forEach(element =>
    {
        tmp = $("<option></option>");
        //tmp.prop('value',element.name);
        tmp.val(element.name);
        tmp.text(element.name);
        $("#addNomenklatorFolderSelect").append(tmp);
    })
}

function fillKeyUsers(users)
{
    if(users == null)
        return;
    $("#addNomenklatorKeyUserSelect").find('option').remove()
    users.forEach(element =>
    {
       let tmp = $("<option></option>");
       //tmp.prop('value',element.id);
       tmp.val(element.id);
       tmp.text(element.name);
        $("#addNomenklatorKeyUserSelect").append(tmp);
    });

}

function addUrlInput(where)
{
    var tmpDir = $("<div class='nomenklatorImage'></div>");

    tmpDir.append("<label for='url'>Image URL</label> <input type='text' name='url'> <br> " +
        "<label for='structure'> Nomenklator structure</label> <textarea name='structure'></textarea><br>"
    );
    where.append(tmpDir);
}

function addNewNomenclatorImage(where)
{
    var d = $("<div class='addNomenclatorImageDiv'></div>");
    var label = $("<label> Upload image  / insert URL </label>");
    var slider = $("<input type='checkbox' name='isLocal'>");
    var sliderDiv = $("<label class='switch'>  </label>");
    sliderDiv.append(slider);
    var sliderLabel = $("<span class='slider round'</span>");
    sliderDiv.append(sliderLabel);


    var struct = $("<label for='structure'> Nomenklator structure</label> <textarea name='structure'></textarea>");
    d.append(struct);
    d.append($("<br>"));
    d.append(label);
    d.append(sliderDiv);
    d.append("<br>");
    //d.append(slider);
    var url = $("<label for='url'>Image URL</label> <input type='text' name='url'>");

    var upload = $("<label for='nomenclatorImage'>Nomenclator Image</label> <input type='file' multiple='multiple' " +
        "class='form-control-file' name='nomenclatorImage'>");
    d.append(url);
    d.append(upload);
    url.hide();
    slider.change(function ()
        {
            console.log(slider.val());
            //e.preventDefault();
            if(slider.is(":checked"))
            {
                console.log("checked");
                url.show();
                upload.hide();
            }
            else
            {
                console.log("Unchecked");
                url.hide();
                upload.show();
            }

        }
    )
    where.append(d);

}