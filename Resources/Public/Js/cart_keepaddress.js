$(document).ready( function() {
    var rdata = sessionStorage.getItem('fdata');
    if (typeof(rdata) !=="object" && rdata !='')  {
        //alert(rdata);
        var obj = JSON.parse(rdata);
        var arr = Object.keys(obj['data']).map((key) => [key, obj['data'][key]]);
        //alert(JSON.stringify(arr));
        var elem = [];
        for(var i in arr) {
            //console.log(array[i]);
            if (~arr[i][0].indexOf('billingAddress') && arr[i][1]) elem.push([arr[i][0],arr[i][1]]);
            if (~arr[i][0].indexOf('shippingAddress') && arr[i][1]) elem.push([arr[i][0],arr[i][1]]);

        }
        //alert(JSON.stringify(elem));
        for(var i in elem) {
            // alert((elem[i][1]));
            var ele=document.getElementsByName(elem[i][0]);
            ele[0].value=elem[i][1];
        }
        sessionStorage.setItem('fdata', '');
    }

    $('#updateCart').on('click', function(e) {
        e.stopPropagation();
        var formData = $('#form-order');
        //alert(formData);
        var data = {};
        $('#form-order').serializeArray().map(function(x){data[x.name] = x.value;});
        sessionStorage.setItem('fdata', JSON.stringify({data}, null, 4));
        $('#updateCart').click();
    });

    $('#set_quantity').on('blur', function(e) {
        document.getElementById('updateCart').click();
    });
});

function clickquantity(e) {
    e.stopPropagation();

}