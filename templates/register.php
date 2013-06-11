<h1>Register server</h1>

<p>Register a server which is gonna act as an identity client.</p>

<form method="post">

    <fieldset>
	<legend>About You</legend>
	
	<p>
	    <label for="requester_name">Your name</label><br/>
	    <input class="text" id="requester_name"  name="requester_name" type="text" value="" />
	</p>
	<p>
	    <label for="application_notes">Your name</label><br/>
	    <input class="text" id="application_notes"  name="application_notes" type="text" value="" />
	</p>
	
	<p>
	    <label for="requester_email">Your email address</label><br/>
	    <input class="text" id="requester_email"  name="requester_email" type="text" value="" />
	</p>
    </fieldset>
    
    <fieldset>
	<legend>Location Of Your Application Or Site</legend>
	
	<p>
	    <label for="application_uri">URL of your application or site</label><br/>
	    <input id="application_uri" class="text" name="application_uri" type="text" value="" />
	</p>
	
	<p>
	    <label for="callback_uri">Callback URL</label><br/>
	    <input id="callback_uri" class="text" name="callback_uri" type="text" value="" />
	</p>
    </fieldset>

    <br />
    <input type="submit" value="Register server" />
</form>

