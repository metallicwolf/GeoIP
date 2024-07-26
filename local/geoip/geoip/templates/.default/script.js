(function (window) {
    'use strict';
    if (window.GeoApp)
        return;

    window.GeoApp = function (arParams) {
        document.getElementById('geoform').addEventListener('submit', function (e) {
            e.preventDefault();
            if (validateIP(this.ip.value)) {
                hideData();
                BX.ajax.runComponentAction(
                    'geoip:geoip',
                    'searchIP',
                    {
                        mode: 'class',
                        data: {
                            ip: this.ip.value,
                            hlIblockID: arParams['HL_IBLOCK_ID'],
                            params: arParams
                        }
                    }).then(response => {
                        if (response['data']['success']){
                            showData(response['data']['data'])
                        }else{
                            showDataEmpty(response['data']['error']);
                        }
                })
            }else {
                showAlert('Указан не корректный IP адрес');
            }
        })

        function validateIP(ip) {
            return /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test(ip);
        }

        function showData(data){
            let ipdata = document.getElementById('ipdata');
            if (ipdata){

                ipdata.innerText = 'ip: ' + data['ip'];
                if (data['country']['name_ru'] !== '' && data['country']['name_ru'] !== 'null'){
                    ipdata.innerText += ', ' + data['country']['name_ru'];
                }
                if (data['region']['name_ru'] !== '' && data['region']['name_ru'] !== 'null'){
                    ipdata.innerText += ', ' + data['region']['name_ru'];
                }
                if (data['city']['name_ru'] !== '' && data['city']['name_ru'] !== 'null'){
                    ipdata.innerText += ', ' + data['city']['name_ru'];
                    ipdata.innerText += ', (широта: ' + data['city']['lat'];
                    ipdata.innerText += ', долгота: ' + data['city']['lon'] + ')';
                }
                ipdata.style.display = 'block';
            }
        }

        function showDataEmpty(text='Данных не найдено'){
            let ipdata = document.getElementById('ipdata');
            if (ipdata){
                ipdata.innerText = text;
                ipdata.style.display = 'block';
            }
        }

        function hideData(){
            let ipdata = document.getElementById('ipdata');
            if (ipdata){
                ipdata.style.display = 'none';
                ipdata.innerText = '';
            }
        }

        function showAlert(text){
            let alert = document.getElementById('alert');
            if (alert){
                alert.classList.add('error');
                alert.innerText = text;
            }
        }

        function hideAlert(){
            let alert = document.getElementById('alert');
            if (alert){
                alert.classList.remove('error');
                alert.innerText = '';
            }
        }
        document.getElementById('ip').addEventListener('input', (e) => {
            hideAlert();
            hideData();
            if (!validateIP(e.target.value)){
                e.target.style.borderColor = 'red';
            }else{
                e.target.style.borderColor = '#e1e1e1';
            }
        });
    }
})(window);