var cipherCreatorJson = null
$(document).ready(function ()
{
    $("#cipherCreatorJson").change(function (e){
        console.log($(this)[0].files[0].name)


            let fileReader = new FileReader();
            fileReader.onload = function ()
            {
                try{
                    var tmpD = JSON.parse(fileReader.result)
                    cipherCreatorJson = tmpD;
                    console.log(cipherCreatorJson)
                }
                catch (err)
                {
                    console.log(err)
                    $("#cipherCreatorJson").val('');
                }

            }
            fileReader.readAsText($(this)[0].files[0])
    })

    $("#cipherCreatorForm").submit(function (e)
    {
        console.log(localStorage.getItem("token"))
        e.preventDefault();
        if(cipherCreatorJson !== null)
        {
            if($("#nomenclatorImage")[0].files.length !== 0)
            {
               let data = new FormData()
                data.append("nomenclatorImage", $("#nomenclatorImage")[0].files[0])
                data.data = JSON.parse(cipherCreatorJson)
                $.ajax({
                    beforeSend: function(request) {
                        request.setRequestHeader("authorization", localStorage.getItem("token"));
                    },
                    url: 'https://keys.hcportal.eu/api/cipherCreator',
                    type: 'POST',
                    enctype: "multipart/form-data",
                    contentType :  "multipart/form-data",
                    data: data,
                    processData: false,
                    cache : false,
                    success: function (data)
                    {
                        console.log(data);

                    },
                    error: function (request, status, error) {
                        console.log(request.responseText);
                        console.log(status);
                        console.log(error);
                    }
                });
            }
            else
            {
                console.log(cipherCreatorJson);
                $.ajax(
                    {
                        beforeSend: function (request) {
                            request.setRequestHeader("authorization", "VRwMy5o9uHcb83u7ZRZk:FW8jWCCL63Z+IImVk6qRS23Y3k4aIyLnmDWWOyQ45h9y");
                        },
                        url: 'https://keys.hcportal.eu/api/cipherCreator',
                        type: 'POST',
                        enctype: "application/json",
                        contentType: "application/json",
                        processData: false,
                        data: JSON.stringify(cipherCreatorJson),
                        success: function (data) {
                            console.log(data);

                        },
                        error: function (request, status, error) {
                            console.log(request.responseText);
                            console.log(status);
                            console.log(error);
                        }
                    }
                )
            }

        }

    })
})