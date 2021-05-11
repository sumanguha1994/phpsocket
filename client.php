<html>
<body>
    <span id="connectmsg" style="color: red"></span>
    <br>
    <div id="root"></div>
    <script>
        var host = 'ws://127.0.0.1:80/socket/one2one/server.php';
        var socket = new WebSocket(host);
        socket.onopen = function(event) { 
			document.getElementById('connectmsg').innerHTML = "Connection successfull";	
            socket.send('Hello Server one time!'); 
            socket.send('Hello Server 2nd time!'); 
		}
        socket.onmessage = function(e) {
            document.getElementById('root').innerHTML = e.data;
        };
        socket.onerror = function(event){
			document.getElementById('connectmsg').innerHTML = "Not Connected !!";	
		};
		socket.onclose = function(event){
			document.getElementById('connectmsg').innerHTML = "Connection Closed.";
		}; 
    </script>
</body>
</html>