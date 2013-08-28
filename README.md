VBShout for Android
===================


What is it?
-----------
It's Android implementation of [vBShout by DragonByte Tech][dbtlink] ShoutBox plugin for [VBulletin][vblink] forums engine.
More info about ShoutBox can be found on DragonByte site.

How it works?
-------------
I created simple class in PHP and Android application witch provide access to original SB functions like:

- User authentication
- Shout list display and syncing
- Posting/Editing/Deleting Shouts
- PM implementation
- SB permissions implementation (checking if user can post, edit, delete ect.)
- optimized for mobile transmission
- JSON data format

PHP/Server side implementation source code can be found in this repository, Android application source code is not OpenSource.

How to install/use?
-------------------
#### Developers/Admins
....If you want to enable in app ShoutBox usage on your site, you have to place php file [name soon] in your forum root directory... That's it.

##### Users
....if you want to use vBShout in Android you need to download and install Android Application, then follow in app instructions.

....If your favorite site Admin placed php bridge on server, you're got do go, otherwise you need to ask him about it. App will automatically check if file exist on server.


When I can get it?
------------------
For now access to application have only few users, because it's in development.

Later application will be available in Google Play Store...
     
.

.

.

************************************************************
.

.

.

vBShout dla Android'a
=====================

O co chodzi?
------------
W tym repozytorium znajdują się pliki implementacji pluginu ShoutBox'a [vBShout by DragonByte Tech][dbtlink] dla skryptu for [VBulletin][vblink].
Więcej informacji o samym ShoutBox'ie na stronie DragonByte.


Jak to działa?
--------------
Stworzyłem prostą klasę w PHP oraz aplikację dla Android'a która umożliwia obsługę ShoutBox'a w Systemie Android w sposób natywny.
Lista funkcji:

- Uwierzytelnianie użytkownika
- Wyświetlanie i synchronizowanie listy wiadomości w czasie rzeczywistym
- Wysyłanie/Edytowanie/Usuwanie wiadomości
- Obsługa PM na ShoutBox'ie
- Obsługa przywilejów na SB (Czy użytkownik ma prawo do pisania, edycji usuwanie itd.)
- Zoptymalizowany przesył danych dla transmisji mobilnych (wykorzystuje mniej danych niż korzystanie z SB w przeglądarce)
- Format przesyłanych danych to JSON

W tym repozytorium znajdują się źródła plików PHP, które służą jako most między aplikacją, a samym ShoutBox'em.
Źródła aplikacji nie są publiczne

Jak zainstalować/używać?
------------------------
##### Deweloperzy/Administratorzy
....Jeśli chcesz, aby z ShoutBox'a zainstalowanego na twojej stronie można było korzystać w aplikacji dla Android'a, musisz tylko umieścić plik [nazwa wkrótce] w głównym katalogu forum, to wszystko...

##### Użytkownicy
....Jeśli chcesz korzystać z ShoutBox'a twojej ulubionej strony w urządzeniu z Androidem powinieneś pobrać i zainstalować Aplikacje [Linki wkrótce] i postępować zgodnie z instrukcjami w niej zawartymi

....Aplikacja sama wykryje czy na stronie, z której chcesz skorzystać zainstalowany jest w/w skrypt, jeśli nie poproś administracje o dodanie go, aby móc korzystać z SB


Gdzie mogę dostać aplikacje?
----------------------------
W tej chwili dostęp do aplikacji ma niewielkie grono użytkowników, którzy testują jej funkcje.

Kiedy zakończę testy aplikację będzie można znaleźć w sklepie Google Play



[vblink]: http://vbulletin.com
[dbtlink]: http://www.dragonbyte-tech.com/vbecommerce.php?do=product&productid=2