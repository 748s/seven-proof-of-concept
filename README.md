### Seven:
- *Seven* is a lightweight, extensible framework for rapid application development using PHP/MySQL.
- It's meant to give you a compact yet effective development toolset without getting in your way
- I&rsquo;ve packaged it with [Bootstrap](http://getbootstrap.com) for a responsive front-end, though it could easily be replaced with any other front-end framework. I&rsquo;ve also added in a few lines of css specific to *Seven*
- The back-end uses [Twig](http://twig.sensiolabs.org) as a templating engine, and PDO for database access
- **Routing is simple**: configured via JSON file
- **Database queries are simple**: The Seven Controller class provides access to an easy-to-use database-abstraction class, with both shorthand methods and the ability to write your own queries
- **Permissions are simple**: Requiring that a user be logged in to access a page is as easy as setting `$requireLogin = true;` for that Controller,
and more sophisticated permissions requirements can be met with the `getPermission()` method within a Controller
- **Form Submissions are simple**: Seven's *FormUtility* class makes it easy to validate data and - in the event of incomplete forms - provide feedback to the user on a field-by-field basis

### Installation:
- Via the command-line, go to the virtual host directory of your choice, and <br />`git clone https://github.com/748s/seven-proof-of-concept .`
- Install Seven's dependencies with `composer update`
- Then copy the sample config file to config.json: `cp config.json.sample config.json`
- Using the text/code-editor of your choice, open config.json and fill in the appropriate database information.
- In the event that you&rsquo;re implementing *Seven* into an existing application or database,
add the field names which you generally use for timestamping record-creation and record-updating;
the &lsquo;put&rsquo; method will automatically add or update these values *whenever they exist in that table*.
Or leave them blank if you don&rsquo;t want &lsquo;put&rsquo; to do anything with those fields and/or if you&rsquo;re fortunate enough to be using MySQL 5.6 with multiple auto-timestamped fields per table.
- In a MySQL client, add the users and errors table by running the following queries:<br />
`CREATE TABLE users(id int unsigned not null auto_increment primary key, emailAddress varchar(100) not null, password varchar(100) null, firstName varchar(25) not null, lastName varchar(25) not null, created datetime, updated datetime);`<br />
`CREATE TABLE errors(id int unsigned not null auto_increment primary key, type varchar(50) null, message tinytext null, file varchar(50) null, line varchar(5) not null default 0, uri tinytext null, userId int unsigned null, ipAddress varchar(50) null, userAgent tinytext null, referer tinytext null, created datetime);`
- Finally, let's add a user account for you (email address: *seven@748s.com*, password: *password*) so that you can login and experiment with the app:<br />
`INSERT INTO users (emailAddress, password, firstName, lastName, created, updated) VALUES ('seven@748s.com', 'password', 'Seven', 'FortyEightS', NOW(), NOW());`

### Guided Tour:
As a proof-of-concept, right now this is setup as a small app with a couple of pages. I&rsquo;ve deliberately made the UserController fairly full-featured in an effort to demonstrate Seven&rsquo;s capabilities, and I'll use that Controller to walk you around the framework.
#### Routing:
1. First we'll create a route, which means we're connecting a request with a controller. So in routes.json we have the route-ClassName pair `"users": "UsersController"`. Any request to /users will try to be routed to the UsersController.
    - If you prefer to put Controllers in different directories within /src/Controllers, then the className should read `"users": "MyDirectory\\UsersController"`.<br />
    - You can create routes as many segments deep as you like, simply by making the routes object multi-dimensional:
    `"users": {"members": "MembersControllers",  "admins": "AdminsController"}`
2. When the app gets a request and finds a matching route, it then looks for an appropriate method within that controller:
    - If there are items in the $_POST array, then it will look for the postAction method.
    - Otherwise, it will look for the words 'add', 'edit', and 'delete' - if any of those words are the next segment of the request (e.g. '/users/add'), then it will look for a matching method.
    - Otherwise, if there are any additional segments in the request (e.g. /users/seven, or /users/2304), then it will look for the getAction method
    - Finally, if there are no additional segments (i.e. '/users'), it will look for the defaultAction method.
3. In the event that the Router cannot find the controller with the appropriate method, the Controller will respond with a 404.
4. If *there is* an appropriate controller and method, the Router then checks that Controller for permission:
    ##### requireLogin:
    - If this controller has `public $requireLogin = true;`, then the user must be logged in to be able to access this controller. If not, anyone can access it.
    - If the user is not logged in but needs to be, the app responds with a 401.
    ##### getPermission
    - Furthermore, we can be very specific/granular with regard to permissions using the getPermission method within a controller.
    - As you'll see in the UsersController, in addition to being required to be logged in to access the page, this getPermission method makes it impossible for a user to delete their own record in the users table.
    - Anytime getPermission returns `false`, the app responds with a 403.
5. Finally: If we now have a Controller, a method, and permission, the app executes that method.

#### Controllers:
1. Of course every controller should start with it's namespace, then 'use' blocks, and finally the class declaration, which extends the Seven\Controller class.
2. As discussed above, you can use the attribute `$requireLogin` and/or the getPermission method to restrict access to any controller and it's methods.
3. Further, you can replace or add to *almost any* of Seven's built-in responses and classes by adding a class to the Extensions directory.
As an example, I've done this with Twig (/src/Extensions/TwigExtension.php), and you can add/tweak Seven's features by adding extensions for any of the following:
    - Controller
    - Permissions
    - Twig
    - DB
    - ErrorHandling
4. Once you're working within a controller's method, I've tried to make the database work as simple as possible with shorthand methods:
    ##### Shorthand Methods:
    - `$this->DB->getOneById('users', 1);` will get you the record from the users table where the primary key = 1
    - `$this->DB->deleteOneById('users', 1);` will delete the record from the users table where the primary key = 1, and return the number of rows matched for the query
    - `$this->DB->put('users', $user, 1);` will update the record in the users table where the primary key = 1 and return true if the record was updated.
    - `$this->DB->put('users', $user);` will insert a new record into the users table and return the primary key of the new record
    ##### Longhand Queries:
    - `$query = 'SELECT * FROM users WHERE created < :created ORDER BY LOWER(created) DESC'`
    - `$params = array(':created' => '2016-05-01');`
    - `$newUsers = $this->DB->select($query, $params);`
    - All of the following methods take the same arguments (i.e. $query, $params), but return different results depending on what you are trying to accomplish
        1. `insert($query, $params = array())` returns the primary key of the record inserted
        2. `select($query, $params = array())` returns an array of results
        3. `selectOne($query, $params = array())` returns the first result
        4. `update($query, $params = array())` returns the number of records affected
        5. `delete($query, $params = array())` returns the number of records affected
5. I often find it useful to tell the user when they have accomplished an action.
So once the app has accomplished it's task, I can `$this->setMessage('blue', 'You just updated a user', true);`
6. Once you gotten/manipulated your data, you can then load and access the Twig with `$this->loadTwig();`
You can also load and render in a single line: `$this->loadTwig()->render('users.default.html.twig', array('users' => $users));`
7. Seven's Twig class automatically looks for the message you set, and sends the message object to the template.
So as long as any template you create has {% include 'includes/message.html.twig' %} (or whatever you choose as your message template), the message will be displayed on the succeeding page

#### The FormUtility
As an application developer you work a lot with forms. At present the FormUtility that this is shipping with needs many more methods. But it's a good start. Here's how it works:
1. The FormUtility validates data from the $\_POST array, saves good/safe data in it's own $data array, and notes errors in it's $errors array.
2. The methods vary, but generally you send the FormUtility the name of the form input, and an error message for that input or false.<br />
    `$FormUtility->isCleanString('firstName', 'First Name is required');`<br />
    `$FormUtility->isCleanString('lastName', false);`<br />
    `$FormUtility->isEmailAddress('emailAddress', 'A valid email address is required');`
3. So for the form which I am validating here, firstName is required, lastName is optional but let's trim(strip\_tags()) on it anyway, and emailAddress is required and required to be a valid email address<br />
    `$user = $FormUtility->getData();`
4. Here, $user will be an array containing the sanitized form data - but only those fields which you validated and it will never contain any additional fields, regardless of what is in the $_POST. I.e. in the above case it will have at most values for firstName, lastName, and emailAddress
    `if($errors = $FormUtility->getErrors()) {...`
5. If the form submission did not validate (in this case, either the firstName was left blank or the emailAddress submitted was not a valid email address), the we are returned an array of input names and error messages for those fields.
6. Once we have the array of errors, `$this->setFormErrorMessage($errors);` will transform them into an error message, which will be displayed when you return them to the incomplete form
7. If the user's form is complete, then you can take the sanitized data ($user), push it to the database using the put method, set a nice success message, and give them a header directing them to the page for that entity or class.

### Requirements:
- PHP 5.5
- MySQL
- [Composer](http://getcomposer.org)

### Other Notes:
- The FormUtility definitely needs to be built out to account for other kinds of data (dates, etc).
- MySQL 5.6 is capable of having two auto-timestamped fields for tables - one for when the record was inserted, another for when it gets updated
- This proof-of-concept does not - for simplicity's sake - encrypt passwords. If you've read this far you can easily implement PHP's password_hash/password_verify functions (or any other method of your choosing) into whatever app you build with this :-)
- I also need to build in a better way to add Twig Filters
- That said: although *Seven* definitely needs further refinement before it goes &lsquo;to press&rsquo;, the goal (as stated above) is to provide a simple but effective toolset, which each developer extends based on their own practices. I.e. I *expect* any substantial apps/developers to create extensions for every extensible class: add filters to Twig, add their own ControllerExtension which alters the 401, 403, and 404 responses, etc.
