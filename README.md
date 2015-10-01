# uLogin

Donate link: https://ulogin.ru  
Tags: ulogin, login, social, authorization  
Requires at least: 3.x.x  
Tested up to: 3.4.4  
Stable tag: 2.0.1  
License: GNU General Public License, version 2  

**uLogin** - это инструмент, который позволяет пользователям получить единый доступ к различным Интернет-сервисам без необходимости повторной регистрации,а владельцам сайтов - получить дополнительный приток клиентов из социальных сетей и популярных порталов (Google, Яндекс, Mail.ru, ВКонтакте, Facebook и др.)


## Установка

- Загрузите и установите архив плагина через Админку сайта в разделе "Расширения - Менеджер расширений - Установка",
вкладку "Загрузить файл пакета".

При успешной установке расширения устанавливаются

 - компонент uLogin;
 - модуль "uLogin - Мои аккаунты";
 - модуль "uLogin - Войти с помощью";
 - плагин "Аутентификация - uLogin".

Также устанавливается новая группа для новых, регистрирующихся через uLogin, пользователей (группа uLogin).

После установки расширения настройте модули *"Войти с помощью"* и *"Мои аккаунты"*: укажите их позицию в шаблоне и опубликуйте.  
Авторизация через uLogin готова к работе.

Более детальное описение виджетов и компонента смотрите ниже.


## Удаление

Для удаления расширения uLogin достаточно удалить только компонент uLogin "Расширения - Менеджер расширений: Управление" - выберите компонент uLogin, нажмите кнопку "Деинсталлировать".  
Модули и плагин uLogin удалятся при этом автоматически.

Данные об аккаунтах пользователей сохранятся в базе данных на случай повторной установки ulogin.


## Настройки компонента uLogin

Компонент uLogin имеет следующие параметры:

- Значение поля **uLogin ID** - необязательный параметр. Нужен для более детальной настройки виджетов uLogin. Больше информации смотрите в пункте *"Настройки виджета uLogin"*
- Группа для новых пользователей - группа для новых, регистрирующихся через uLogin, пользователей.
Позволяет более детально настроить права для данной категории пользователей.
По умолчанию - группа **uLogin**, наследуемая от группы пользователей **Registered**.


## Модули uLogin

Пакет установки включает в себя 2 модуля:

- *uLogin - Войти с помощью* - обеспечивает вход/регистрацию пользователей через популярные социальные сети и порталы;
- *uLogin - Мои аккаунты* - позволяет пользователю просматривать подключенные аккаунты соцсетей, добавлять новые.

Данные модули при установке помещаются в список модулей сайта (см. страницу "Менеджер модулей: Модули") и принимают состояние "Не опубликовано" без указания позиции.  
Для работы модулей укажите их позицию и опубликуйте.

Модули *"Войти с помощью"* и *"Мои аккаунты"* имеют необязательный параметр **uLogin ID** (как и компонент uLogin), описание которого смотрите в пункте *"Настройки виджета uLogin"*.


## Настройки виджета uLogin

При установке расширения uLogin авторизация пользователей будет осуществляться с настройками по умолчанию.  
Для более детальной настройки виджетов uLogin Вы можете воспользоваться сервисом uLogin.

Вы можете создать свой виджет uLogin и редактировать его самостоятельно:

для создания виджета необходимо зайти в Личный Кабинет (ЛК) на сайте http://ulogin.ru/lk.php, добавить свой сайт к списку Мои сайты и на вкладке Виджеты добавить новый виджет. После этого вы можете отредактировать свой виджет.

**Важно!** Для успешной работы плагина необходимо включить в обязательных полях профиля поле **Еmail** в Личном кабинете uLogin. Заполнять поля в графе «Тип авторизации» не нужно, т.к. расширение uLogin настроено на автоматическое заполнение данных параметров.

Созданный в Личном Кабинете виджет имеет параметр **uLogin ID**.  
Скопируйте значение **uLogin ID** вашего виджета в соответствующее поле *настройки компонента uLogin* на вашем сайте и сохраните настройки.  
В модулях *"Войти с помощью"* и *"Мои аккаунты"* также можете указать значение **uLogin ID** отличное от значения **uLogin ID** для компонента.

Если всё было сделано правильно, виджет изменится согласно вашим настройкам.

## Изменения

####2.0.1.
* Устранена ошибка, вызывающая некорректное отображение сообщений uLogin.
* Блок для вывода сообщений добавлен в html код виджета.
* Устранено некорректное отображение иконок соцсетей при синхронизации аккаунтов.
* Добавлен вывод ошибок при регистрации пустых обязательных полях.
* Рефакторинг кода.

####2.0.0.
* Релиз.
