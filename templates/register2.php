<h1>Register server</h1>

<p>Register a server which is gonna act as an identity client.</p>


    <form method="post">
      <p>
        <label for="client_id">Client ID:</label>
        <input type="text" name="client_id" id="client_id" />
      </p>
      <p>
        <label for="client_secret">Client Secret (password/key):</label>
        <input type="text" name="client_secret" id="client_secret" />
      </p>
      <p>
        <label for="redirect_uri">Redirect URI:</label>
        <input type="text" name="redirect_uri" id="redirect_uri" />
      </p>
      <input type="submit" value="Submit" />
    </form>
