# Elevenlabs TTS for AI Engine

This is a little addon for the MEOW AI Engine in form of a WordPress Plugin that adds TTS for the Output in the Chat.

Basically it catches the Response from the GPT Model, sends it to the Elevenlabs API and plays the converted MP3 File in the AI Engine Chatbot.

While this plugin is fully working, you should see this more as a starting point for whatever you want to archive. Its a very basic implementation.

# Installation & Configuration

Install it like any other wordpress plugin. Plugins -> Add New -> Upload Plugin) and activate it.

The configuration is kept quite simple. After activating the plugin you will find a new section called "Text to Speech" within the WordPress Admin Settings section. 
In there you need to set the API key of Elevenlabs. After the API key is tested, you will get a list of the available voices from your Elevenlabs Profile. Choose the voice you want to use and press save.

Thats it. As soon as you start a conversation now, the text response will be gone and you will find a audio player within the chat. 

# Some remarks and things to consider

Performance: While i was afraid that the performance would be bad, since the response from the text model has to be sent to the Elevenlabs API, gets converted and than needs to be downloaded to the server again, i can say that i am now supprised how well this works. Especially for short answers, the response is usually below 10 seconds. 
However, if you have longer responses, the convertion on the Elevenlaps API can take some time!

Auto Play: Keep in mind that some browsers do not play audio or video files automatically. Since this is a setting on the client side, there is not a lot we can do here. The chat client will need to press the play button.






