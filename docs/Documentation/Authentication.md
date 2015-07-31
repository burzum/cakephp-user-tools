If you'd like that your Authentication/Authorization rules apply to the views of the plugin, you have to add the 
corresponding empty functions in your UsersController.

So for example if you'd like to only allow logged in users to access the change_password view, add the following code to your UsersController:
public function change_password() {

}
