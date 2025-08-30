const {
  formatReceipt,
  delayMsg,
  prepareMediaMessage,
} = require("../lib/helper");
const { sock } = require("./store");
const { Sticker, StickerTypes } = require("wa-sticker-formatter");
const { Button, formatButtonMsg } = require("./../dto/button");
const { ulid } = require("ulid");
const { Section, formatListMsg } = require("./../dto/list");
const path = require('path');

// Function to convert localhost URL to file path
function convertLocalUrlToPath(url) {
  try {
    if (url.includes('127.0.0.1:8000/storage') || url.includes('localhost:8000/storage')) {
      // Extract the path after /storage/
      const storagePath = url.split('/storage/')[1];
      // Construct the full path relative to Laravel storage
      return path.join(process.cwd(), '..', 'storage', 'app', 'public', storagePath);
    }
    return url;
  } catch (error) {
    console.error("Error converting URL to path:", error);
    return url;
  }
}

// text message
const sendText = async (token, number, text, delay = 0) => {
  try {
    await delayMsg(delay * 1000, sock[token], number);
    const sendingTextMessage = await sock[token].sendMessage(
      formatReceipt(number),
      { text: text }
    ); // awaiting sending message

    return sendingTextMessage;
  } catch (error) {
    console.log(error);

    return false;
  }
};
const sendMessage = async (token, number, msg, delay = 0) => {
  try {
    await delayMsg(delay * 1000, sock[token], number);
    const sendingTextMessage = await sock[token].sendMessage(
      formatReceipt(number),
      JSON.parse(msg)
    );
    return sendingTextMessage;
  } catch (error) {
    return false;
  }
};

async function sendMedia(
  token,
  destination,
  type,
  url,
  caption,
  ptt,
  filename,
  delay = 0
) {
  try {
    console.log("[sendMedia] Starting with params:", {
      token, destination, type, url, caption, filename
    });

    const number = formatReceipt(destination);
    let ownerJid = sock[token].user.id.replace(/:\d+/, "");

    //for vn
    if (type == "audio") {
      console.log("[sendMedia] Sending audio message");
      return await sock[token].sendMessage(number, {
        audio: { url: url },
        ptt: true,
        mimetype: "audio/mpeg",
      });
    }

    // for send media ( document/video or image)
    console.log("[sendMedia] Preparing media message with:", {
      caption: caption ? caption : "",
      fileName: filename,
      media: url,
      mediatype: type !== "video" && type !== "image" ? "document" : type,
    });

    // Convert localhost URL to file path if needed
    const mediaPath = convertLocalUrlToPath(url);
    console.log("[sendMedia] Using media path:", mediaPath);

    // Test media accessibility
    try {
      if (mediaPath.startsWith('http')) {
        const axios = require('axios');
        const mediaResponse = await axios.head(mediaPath);
        console.log("[sendMedia] Media URL is accessible:", {
          status: mediaResponse.status,
          contentType: mediaResponse.headers['content-type'],
          contentLength: mediaResponse.headers['content-length']
        });
      } else {
        const fs = require('fs');
        if (!fs.existsSync(mediaPath)) {
          throw new Error(`File not found: ${mediaPath}`);
        }
        const stats = fs.statSync(mediaPath);
        console.log("[sendMedia] Local file is accessible:", {
          size: stats.size,
          path: mediaPath
        });
      }
    } catch (mediaError) {
      console.error("[sendMedia] Media is not accessible:", mediaError.message);
      throw new Error(`Media is not accessible: ${mediaError.message}`);
    }

    const generate = await prepareMediaMessage(sock[token], {
      caption: caption ? caption : "",
      fileName: filename,
      media: url,
      mediatype: type !== "video" && type !== "image" ? "document" : type,
    });

    if (!generate || !generate.message) {
      console.error("[sendMedia] Failed to prepare media message");
      throw new Error("Failed to prepare media message");
    }

    console.log("[sendMedia] Media prepared successfully");
    const message = { ...generate.message };

    await delayMsg(delay * 1000, sock[token], number);

    console.log("[sendMedia] Sending media message");
    const result = await sock[token].sendMessage(number, {
      forward: {
        key: { remoteJid: ownerJid, fromMe: true },
        message: message,
      },
    });

    console.log("[sendMedia] Media sent successfully");
    return result;
  } catch (error) {
    console.error("[sendMedia] Error:", error);
    console.error("[sendMedia] Stack:", error.stack);
    throw error; // Re-throw to be handled by caller
  }
}

// button message
async function sendButtonMessage(
  token,
  number,
  button,
  message,
  footer,
  image = null
) {
  /**
   * type is "url" or "local"
   * if you use local, you must upload into src/public/temp/[fileName]
   */
  let type = "url";
  const msg = message;
  try {
    const buttons = button.map((x, i) => {
      return new Button(x);
    });
    const message = await formatButtonMsg(
      buttons,
      footer,
      msg,
      sock[token],
      image
    );
    const msgId = ulid(Date.now());
    const sendMsg = await sock[token].relayMessage(
      formatReceipt(number),
      message,
      { messageId: msgId }
    );
    return sendMsg;
  } catch (error) {
    console.log(error);
    return false;
  }
}

async function sendTemplateMessage(token, number, button, text, footer, image) {
  try {
    if (image) {
      var buttonMessage = {
        caption: text,
        footer: footer,
        viewOnce: true,
        templateButtons: button,
        image: { url: image },
        viewOnce: true,
      };
    } else {
      var buttonMessage = {
        text: text,
        footer: footer,
        viewOnce: true,

        templateButtons: button,
      };
    }

    const sendMsg = await sock[token].sendMessage(
      formatReceipt(number),
      buttonMessage
    );
    return sendMsg;
  } catch (error) {
    console.log(error);
    return false;
  }
}

// list message
async function sendListMessage(
  token,
  number,
  list,
  text,
  footer,
  title,
  buttonText,
  image = null
) {
  try {
    const sections = list.map((sect) => new Section(sect));

    const listMsg = await formatListMsg(
      sections,
      footer,
      text,
      sock[token],
      image
    );

    const msgId = ulid(Date.now());
    const sendMsg = await sock[token].relayMessage(
      formatReceipt(number),
      listMsg,
      { messageId: msgId }
    );
    return sendMsg;
  } catch (error) {
    console.log(error);
    return false;
  }
}

async function sendPollMessage(token, number, name, options, countable) {
  try {
    const sendmsg = await sock[token].sendMessage(formatReceipt(number), {
      poll: {
        name: name,
        values: options,
        selectableCount: countable,
      },
    });

    return sendmsg;
  } catch (error) {
    console.log(error);
    return false;
  }
}

async function sendLocation(waToken, recipient, latitude, longitude) {
  try {
    await delayMsg(1000, sock[waToken], recipient);
    const sendLocationResult = await sock[waToken].sendMessage(
      formatReceipt(recipient),
      {
        location: { degreesLatitude: latitude, degreesLongitude: longitude },
      }
    );
    return sendLocationResult;
  } catch (error) {
    return false;
  }
}
async function sendVcard(waToken, recipient, name, phone) {
  try {
    const vcard =
      "BEGIN:VCARD\n" + // metadata of the contact card
      "VERSION:3.0\n" +
      "FN:" +
      name +
      "\n" + // full name
      "TEL;type=CELL;type=VOICE;waid=" +
      phone +
      ":+" +
      phone +
      "\n" + // WhatsApp ID + phone number
      "END:VCARD";
    delayMsg(1000, sock[waToken], recipient);
    const sendLocationResult = await sock[waToken].sendMessage(
      formatReceipt(recipient),
      {
        contacts: {
          displayName: name,
          contacts: [{ vcard }],
        },
      }
    );
    return sendLocationResult;
  } catch (error) {
    return false;
  }
}
async function sendSticker(
  waToken,
  recipient,
  mediaType,
  mediaPath,
  message,
  fileName
) {
  const formattedRecipient = formatReceipt(recipient);
  let userId = sock[waToken].user.id.replace(/:\d+/, "");
  const sticker = new Sticker(mediaPath, {
    pack: "",
    author: "",
    type: StickerTypes.FULL,
    quality: 50,
  });
  const buffer = await sticker.toBuffer();
  await sticker.toFile("sticker.webp");
  return await sock[waToken].sendMessage(
    formattedRecipient,
    await sticker.toMessage()
  );
}

module.exports = {
  sendText,
  sendMedia,
  sendButtonMessage,
  sendTemplateMessage,
  sendListMessage,
  sendPollMessage,
  sendMessage,
  sendLocation,
  sendVcard,
  sendSticker,
};
