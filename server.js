"use strict";

const wa = require("./server/whatsapp");
const fs = require("fs");
const dbs = require("./server/database/index");
require("dotenv").config();
const lib = require("./server/lib");
global.log = lib.log;

/**
 * EXPRESS FOR ROUTING
 */
const express = require("express");
const app = express();
const http = require("http");
const server = http.createServer(app);

/**
 * SOCKET.IO
 */
const { Server } = require("socket.io");
const io = new Server(server, {
  pingInterval: 25000,
  pingTimeout: 10000,
});
const port = process.env.PORT_NODE;
const baseUrl = process.env.APP_URL;
app.get("/", (req, res) => {
  res.redirect(baseUrl);
});
app.use((req, res, next) => {
  res.set("Cache-Control", "no-store");
  req.io = io;
  next();
});

const bodyParser = require("body-parser");

app.use(
  bodyParser.urlencoded({
    extended: false,
    limit: "50mb",
    parameterLimit: 100000,
  })
);

app.use(bodyParser.json());
app.use(express.static("src/public"));
app.use(require("./server/router"));

io.on("connection", (socket) => {
  console.log("A user connected");

  socket.on("StartConnection", (data) => {
    console.log("StartConnection received:", data);
    wa.connectToWhatsApp(data, io);
  });

  socket.on("ConnectViaCode", (data) => {
    console.log("ConnectViaCode received:", data);
    wa.connectToWhatsApp(data, io, true);
  });

  socket.on("LogoutDevice", (device) => {
    console.log("LogoutDevice received:", device);
    wa.deleteCredentials(device, io);
  });

  socket.on("disconnect", (reason) => {
    console.log("A user disconnected. Reason:", reason);
  });

  // Keep socket connection alive
  socket.on("ping", () => {
    socket.emit("pong");
  });
});

server.listen(port, () => {
  console.log(`Server running and listening on port: ${port}`);
});
