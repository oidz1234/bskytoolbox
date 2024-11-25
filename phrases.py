import websocket
import json
import sqlite3
import sys
import re
import subprocess
from datetime import datetime



def check_msg(row, text):
    email = row[1]
    phrase = row[2] # the phrase the user wants
    pattern = r'\b' + re.escape(phrase.lower()) + r'\b'
    matches = re.findall(pattern, text.lower())
    if matches:
        print('store this msg')
        print(phrase)
        print(text)
        print(email)
        subprocess.run([
        "mail",
        "-s", f"Your phrase {phrase} has been mentioned on bluesky",
        email,
        "-r", "noreply@bskytoolbox.com"
        ], input=f'{text} \n\n\n\n ------------- \n\n\n\n yeah I have no idea how to link the post... \n\n\n unsubscribe https://bskytoolbox.com/?action=unsubscribe&email={email}', text=True)

        print(f'{datetime.now()} : sent {text}, to {email}')

def on_message(ws, message):
    try:
        # Parse the JSON message
        data = json.loads(message)

        # Extract the fields
        did = data.get("did", "Unknown")
        created_at = data.get("commit", {}).get("record", {}).get("createdAt", "Unknown")
        text = data.get("commit", {}).get("record", {}).get("text", "Unknown")
        

        try:
            table_name = "phrases"  # Replace with your table name
            cursor.execute(f"SELECT * FROM {table_name}")
            rows = cursor.fetchall()

            # Process each row
            for row in rows:
                check_msg(row, text)

        except sqlite3.Error as e:
            print(f"An error occurred: {e}")

        

    

    except json.JSONDecodeError as e:
        print(f"Failed to decode JSON: {e}")
        print(f"Raw message: {message}")

    

def on_error(ws, error):
    print(f"Error: {error}")

def on_close(ws, close_status_code, close_msg):
    print("WebSocket closed")
    if close_status_code or close_msg:
        print(f"Close status code: {close_status_code}, Close message: {close_msg}")

def on_open(ws):
    print("WebSocket connection opened")

if __name__ == "__main__":
    conn = sqlite3.connect("database.sqlite")
    cursor = conn.cursor()
    websocket_url = "wss://jetstream2.us-east.bsky.network/subscribe?wantedCollections=app.bsky.feed.post"  # Replace with your WebSocket URL
    ws = websocket.WebSocketApp(websocket_url,
                                 on_message=on_message,
                                 on_error=on_error,
                                 on_close=on_close)
    ws.on_open = on_open
    ws.run_forever()

