$(document).ready(function()
{
    let loginForm = $("#loginForm");
    let uploadForm = $("#imageUploadForm");
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
        }
        else
        {
            loginForm.hide();
            console.log(localStorage.getItem("token"));
            console.log(localStorage.getItem("expiresAt"));
        }

    }
    else
    {

    }
    $("#logOff").click(function (e)
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
              console.log(data);
            },
            error: function (request, status, error) {
                console.log(request.responseText);
            }
        } );
    });

});