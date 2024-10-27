# Buteco Bot Reloaded

⬅ [Back to README.md](../README.md)

Well I think that as first step to get into the Discord World would be to create a server for you to test the Bot, I recommend create a server especifically for developing and testing the Bot. Create a server is pretty easy, just click in the **Plus** button in your Discord app and follow the instructions.

![](https://github.com/butecodosdevs/butecobot-reloaded/blob/main/docs/images/0.png?raw=true)

Now you'll need to create a Bot in the "Developer Portal", so navigate to [Developer Portal](https://discord.com/developers/applications) -> Applications and click in the **New Application** button in the top right of the screen.

![](https://github.com/butecodosdevs/butecobot-reloaded/blob/main/docs/images/1.png?raw=true)

Type a name for the Bot, mark the checkbox and click on the "Create" button.

![](https://github.com/butecodosdevs/butecobot-reloaded/blob/main/docs/images/2.png?raw=true)

After that you Bot Application will be created, now expand **OAuth2** menu, click on **URL Generator** and in the page that will be opened mark the following checkboxes:

Scopes
- bot
- applications.commands

Bot Permissions
- Administrator (this is for developing only, when creating an account for production choose the proper permissions)

![](https://github.com/butecodosdevs/butecobot-reloaded/blob/main/docs/images/3.png?raw=true)

Now scroll down to the end of the page and you'll see an url, that url will be the one you'll be using to join your Bot with the server you have. You just need to copy and paste the url in any browser and navigate to it.

![](https://github.com/butecodosdevs/butecobot-reloaded/blob/main/docs/images/4.png?raw=true)

![](https://github.com/butecodosdevs/butecobot-reloaded/blob/main/docs/images/5.png?raw=true)

Now let's get the Bot Token to be used by the Bot application. Navigate to [Developer Portal](https://discord.com/developers/applications)  -> Bot, and click in the **Reset Token** button, a new token wil be show in the screen, copy it and add it to your **.env** file in the TOKEN variable.

![](https://github.com/butecodosdevs/butecobot-reloaded/blob/main/docs/images/6.png?raw=true)

For our final step on this screen, we must enable some **Gateway Intents", since this is a develop environment don't worry about enabling all the three as shown in the image below:

![](https://github.com/butecodosdevs/butecobot-reloaded/blob/main/docs/images/8.png?raw=true)

You're almost there, now let's run the docker containers and get back to the **repository** stuff to finish this.

