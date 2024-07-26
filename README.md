# GeoIP
<h3>Поиск местоположения по ip-адресу</h3>

_Демонстрационный_ компонент для 1С-битрикс управление сайтом. Представляет из себя форму ввода адреса.
По введенному IP адресу загружает информацию о местоположении: страна, регион, город, координаты.

Ранее вводимые пользователем кэшируются в Highloadblock.
Первично происходит поиск в Highloadblock. При отрицательном результате происходит запрос к API api.sypexgeo.net.
Содержит валидацию вводимого адреса через регулярное выражение как на фронтэнде, так и на бэкэнде.
Возвращает базовые ошибки.

Критические ошибки можно направлять на email, поставив соотвествующий чек бокс и 
указав в настройках почтовый шаблон, сайт и email.

**Установка:**

Разместить компонент в /local/components/ или /local/templates/clean/components/bitrix/.

Для создания Highloadblock используется бесплатный модуль https://marketplace.1c-bitrix.ru/solutions/sprint.migration/,
который необходимо установить.

Скопировать в соотвествующий каталог миграцию /local/php_interface/migrations/GeoIP20240726095231.php.

Запустить через настройки модуля данную миграцию и убедиться, что таблица Highloadblock **GeoIP** создана.

## GeoIP (EN)

<h3>Location search by ip address</h3>

_Demonstration_ component for 1C-Bitrix site management. It is a form for entering an address.
It downloads location information using the entered IP address: country, region, city, coordinates.

Previously entered by the user are cached in Highloadblock.
The primary search is in Highloadblock. If the result is negative, an API request is made api.sypexgeo.net .
Contains validation of the entered address via a regular expression on both the front-end and back-end.
Returns basic errors.

Critical errors can be sent to email by putting the appropriate check box and
specifying the mail template, website and email in the settings.

**Installation:**

Place the component in /local/components/ or /local/templates/clean/components/bitrix/.

A free module is used to create a Highloadblock https://marketplace.1c-bitrix.ru/solutions/sprint.migration.
That needs to be installed.

Copy the migration to the appropriate directory /local/php_interface/migrations/GeoIP20240726095231.php.

Run this migration through the module settings and make sure that the Highloadblock **GeoIP** table has been created.