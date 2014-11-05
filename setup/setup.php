#!/usr/bin/env php
<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

require "lib/Db.php";

echo <<< WELCOME

Welcome to the WYF Framework
============================
This setup guide would help you get up and running with the WYF framework
as fast as possible.

WELCOME;

$name = get_response(
    "What is the name of your application", 
    null, null, true
);

$home = get_response("Where is your application residing", getcwd(), null, null, true) . "/";
$prefix = get_response("What is the prefix of your application (Enter 'no prefix' if you do not want a prefix)", basename($home));
if($prefix === 'no prefix' || $prefix == '') 
{
    $prefix = '';
}
else
{
    $prefix = "/$prefix";
}

$db = get_db_credentials();

do
{
    try{
        echo "\nTesting your database connection ... ";
        @Db::get($db);
        $failed = false;
    }
    catch(Exception $e)
    {
        //fputs(STDERR, "Failed\n Could not establish a connection to the database.");
        $response = get_response(
            "Failed\nCould not establish a connection to the database. Would you like to provide new credentials", 
            'yes', array('yes','no'), true
        );
        
        if($response == 'yes')
        {
            echo "Getting new credentials ...\n";
            $db = get_db_credentials();        
            $failed = true;
        }
        else
        {
            exit();
        }
    }
} while($failed);

echo("OK");

echo "\nSetting up the configuration files ...\n";

mkdir2($home . 'app');
mkdir2($home . 'app/cache');
mkdir2($home . 'app/cache/code');
mkdir2($home . 'app/cache/menus');
mkdir2($home . 'app/cache/template_compile');
mkdir2($home . 'app/logs');
mkdir2($home . 'app/modules');
mkdir2($home . 'app/modules/system');
mkdir2($home . 'app/temp');
mkdir2($home . 'app/themes');
mkdir2($home . 'app/uploads');

copy_dir("lib/setup/factory/*", "$home/app");
copy("lib/setup/htaccess", ".htaccess");
create_file(
    "$home/app/cache/menus/side_menu_1.html",
    str_replace(
        '{$prefix}', 
        "$prefix", 
        file_get_contents("lib/setup/factory/cache/menus/side_menu_1.html")
    )
);

$system = <<< SYSTEM
<?php
\$redirect_path = "lib/modules/system";
\$package_name = "System";
\$package_path = "system";
\$package_schema = "system";
SYSTEM;
create_file($home . 'app/modules/system/package_redirect.php', $system);
$date = date("jS F, Y H:i:s");

// Generate the default index.php script
$index = <<< "INDEX"
<?php
/**
 * Auto generated by WYF Framework setup script. 
 * $date
 */
require "lib/entry.php";
INDEX;

create_file($home . 'index.php', $index);

// Generate a default documented configuration file
$config = <<< "CONFIG"
<?php
/*
 * Auto generated by WYF Framework setup script. 
 * $date 
 */
        
// Show all errors except for notices
error_reporting(E_ALL ^ E_NOTICE);

/*
 * Use this variable to specify which database configuration to use. 
 */
\$selected = "main";

\$config = array(
    /*
     * Used to specify where the application code resides on the
     * system. The path specified here must be an absolute path to the application's
     * location. Eg. `/var/apps/myapp/`. The last character should be a forward
     * slash (/)
     */
    'home' => "$home",
        
    /*
     * Used to specify the prefix through which the application is accessed. 
     * This tells the framework the automatic prefix to use when dealing with URLs 
     * associated with your application. Prefixes are mostly dictated by the 
     * directories in which the application resides relative to the document root 
     * of the web server.
     */
    'prefix' => "$prefix",
        
    /*
     * Used to specify the name of your application. This is normally used for the 
     * rendering of the HTML title tag and for other informational purposes.
     */
    'name' => "$name",
    
    /*
     * In order to make it possible to keep different database configurations
     * for different purposes, database configurations are presented in a 
     * structured array. A preferred configuration can then be selected by setting
     * the value of the \$selected variable within the configuration file.
     * For example the database configuration below has three different databases
     * specified (main, test and alt_db).
     *     
     *     'db' => array(
     *         "main" => array(
     *             "driver" => "postgresql",
     *             "user" => "postgres",
     *             "host" => "localhost",
     *             "password" => "hello",
     *             "name" => "production",
     *             "port" => "5432",
     *        ),
     *         "test" => array(
     *             "driver" => "postgresql",
     *             "user" => "postgres",
     *             "host" => "localhost",
     *             "password" => "hello",
     *             "name" => "test",
     *             "port" => "5432",
     *         ),   
     *         "alt_db" => array(
     *             "driver" => "postgresql",
     *             "user" => "postgres",
     *             "host" => "localhost",
     *             "password" => "hello",
     *             "name" => "alt",
     *             "port" => "5432",
     *         )                     
     *     ),
     * 
     * The following keys are valid for the database configuration arrays
     * 
     * driver:     This is used to specify which database driver to use. Currently 
     *            the only supported driver is the postgresql driver.
     *     
     *  user:       This is used to specify the username used to connect to the 
     *             database.
     *     
     * host:       This is used to specify the host on which the database server 
     *             resides
     * 
     * password:   The password of the user used to connect to the database server.
     *     
     * name:       The name of the database server.
     *     
     * port:       The port on which the database server runs.
     *     
     */
        
    'db' => array(
    	"main" => array(
            'driver' => 'postgresql',
            'user' => '{$db['user']}',
            'host' => '{$db['host']}',
            'password' => '{$db['password']}',
            'name' => '{$db['name']}',
            'port' => '{$db['port']}'    	
    	)
    ),
            
    /*
     * Configures the caching engine. 
     *         
     * method:     The method to use for the running of the cache engine. Currently
     *             the following cache methods are supported: file, memcache
     *         
     * models:     Specifies whether models should be cached. Caching models
     *             gives a performance boost but for development purposes
     *             this configuration could be set to false.
     *         
     */
    'cache' => array(
        'method' => 'file',
        'models' => true
    ),
            
    /*
     * Specifies whether audit trails should be run or not      
     */
    'audit_trails' => false,
            
    /*
     * Specifies which theme to use for the user interface
     */            
    'theme' => 'default'
);
CONFIG;

create_file($home . 'app/config.php', $config);
create_file($home . 'app/includes.php', "<?php\n");
create_file($home . 'app/bootstrap.php', "<?php\n");

// Try to initialize the wyf framework.
require "vendor/ekowabaka/wyf/wyf_bootstrap.php";

echo "\nSetting up the database ...\n";

Db::query(file_get_contents("lib/setup/schema.sql"));
$username = get_response("Enter a name for the superuser account", 'super', null, true);
$email = get_response('Provide your email address', null, null, true);
Db::query("INSERT INTO system.roles(role_id, role_name) VALUES(1, 'Super User')");
Db::query(
    sprintf(
    	"INSERT INTO system.users
    		(user_name, password, role_id, first_name, last_name, user_status, email) 
    	VALUES
    	 	('%s', '%s', 1, 'Super', 'User', 2, '%s')", 
        Db::escape($username),
        Db::escape($username),
        Db::escape($email)
    )
);
Db::query("
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'can_log_in_to_web', 1, '/dashboard');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_audit_trail_can_add', 1, '/system/audit_trail');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_audit_trail_can_edit', 1, '/system/audit_trail');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_audit_trail_can_delete', 1, '/system/audit_trail');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_audit_trail_can_view', 1, '/system/audit_trail');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_audit_trail_can_export', 1, '/system/audit_trail');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_audit_trail_can_import', 1, '/system/audit_trail');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_roles_can_add', 1, '/system/roles');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_roles_can_edit', 1, '/system/roles');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_roles_can_delete', 1, '/system/roles');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_roles_can_view', 1, '/system/roles');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_roles_can_export', 1, '/system/roles');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_roles_can_import', 1, '/system/roles');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_users_can_add', 1, '/system/users');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_users_can_edit', 1, '/system/users');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_users_can_delete', 1, '/system/users');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_users_can_view', 1, '/system/users');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_users_can_export', 1, '/system/users');
    INSERT INTO permissions (role_id, permission, value, module) VALUES (1, 'system_users_can_import', 1, '/system/users');
"
);

echo "\nDone! Happy programming ;)\n\n";

/**
 * A utility function for creating files. Checks if the files are writable and
 * goes ahead to create them. If they are not it just dies!
 */
function create_file($file, $contents)
{
    if(is_writable(dirname($file)))
    {
        file_put_contents($file, $contents);
        return true;
    }
    else
    {
        fputs(
            STDERR,
            "Error writing to file $file. Please ensure you have the correct permissions"
        );
        return false;
    }
}

/**
 * A function for getting answers to questions from users interractively.
 * @param $question The question you want to ask
 * @param $answers An array of possible answers that this function should validate
 * @param $default The default answer this function should assume for the user.
 * @param $notNull Is the answer required
 */
function get_response($question, $default=null, $answers=null, $notNull = false)
{
    echo $question;
    if(is_array($answers))
    {
        if(count($answers) > 0) echo " (" . implode("/", $answers) . ")";
    }
    
    echo " [$default]: ";
    $response = str_replace(array("\n", "\r"),array("",""),fgets(STDIN));

    if($response == "" && $notNull === true && $default == '')
    {
        echo "A value is required.\n";
        return get_response($question, $answers, $default, $notNull);
    }
    else if($response == "" && $notNull === true && $default != '')
    {
        return $default;
    }
    else if($response == "")
    {
        return $default;
    }
    else
    {
        if(count($answers) == 0)
        {
            return $response;
        }
        foreach($answers as $answer)
        {
            if(strtolower($answer) == strtolower($response))
            {
                return strtolower($answer);
            }
        }
        echo "Please provide a valid answer.\n";
        return get_response($question, $answers, $default, $notNull);
    }
}

function mkdir2($path)
{
    echo("Creating directory $path\n");
    if(!\is_writable(dirname($path)))
    {
        fputs(STDERR, "You do not have permissions to create the $path directory\n");
        die();
    }
    else if(\is_dir($path))
    {
        echo ("Directory $path already exists. I will skip creating it ...\n");
    }
    else
    {
        mkdir($path);
    }
    return $path;
}

function copy_dir($source, $destination)
{
    foreach(glob($source) as $file)
    {
        $newFile = (is_dir($destination) ?  "$destination/" : ''). basename("$file");
        
        if(is_dir($file))
        {
            mkdir2($newFile);
            copy_dir("$file/*", $newFile);
        }
        else
        {
            copy($file, $newFile);
        }
    }
}

function get_db_credentials()
{
    $db = array();
    $db['host'] =     get_response("Where is your application's database hosted", 'localhost', null, true);
    $db['port'] =     get_response("What is the port of this database", '5432', null, true);
    $db['user'] = get_response("What is the database username", null, null, true);
    $db['password'] = get_response("What is the password for the database");
    $db['name'] = get_response("What is the name of your application's database (please ensure that the database exists)", null, null, true);
    
    return $db;
}


