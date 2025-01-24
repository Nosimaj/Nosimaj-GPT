const chatForm = document.getElementById("chat-form");
const chatInput = document.getElementById("chat-input");
const chatOutput = document.getElementById("chat-output");

let askingForRepairOrShop = false;

chatForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const message = chatInput.value.trim();

    if (askingForRepairOrShop) {
        if (message.toLowerCase().includes("shop")) {
            chatInput.value = "find a local bike shop";
        } else {
            chatInput.value = "fix it myself";
        }
        askingForRepairOrShop = false;
    }

    if (!message) return;

    const userMessageContainer = document.createElement('p');
    userMessageContainer.classList.add('user-message');
    userMessageContainer.textContent = message;
    
    chatInput.value = "";

    const botMessageContainer = document.createElement('p');
    botMessageContainer.classList.add('bot-message');

    const messageGroup = document.createElement('div');
    messageGroup.appendChild(userMessageContainer);
    messageGroup.appendChild(botMessageContainer);
    chatOutput.appendChild(messageGroup);

    document.getElementById("loading").style.display = "grid";
    
    try {
        const response = await fetch("gptchat.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ message }),
        });

        document.getElementById("loading").style.display = "none";

        if (response.ok) {
            const data = await response.json();

            // Expecting the 'html' field for both error messages and normal responses
            if (data.html) {
                botMessageContainer.innerHTML = data.html;

                if (data.html.toLowerCase().includes("repair")) {
                    askingForRepairOrShop = true;
                    const additionalMessageContainer = document.createElement('p');
                    additionalMessageContainer.classList.add('bot-message');
                    additionalMessageContainer.textContent = "Can I help you with any more bike stuff?";
                    chatOutput.appendChild(additionalMessageContainer);
                }
            } else {
                console.error("Error: Unexpected response format", data);
                botMessageContainer.textContent = "Something went wrong. Please try again.";
            }

            chatOutput.scrollTop = messageGroup.offsetTop;
        } else {
            console.error("Error communicating with GPTChat API");
            botMessageContainer.textContent = "Unable to connect to the API. Please try again later.";
            chatOutput.scrollTop = messageGroup.offsetTop;
        }
    } catch (error) {
        document.getElementById("loading").style.display = "none";
        console.error("Error communicating with GPTChat API:", error);
        botMessageContainer.textContent = "Unable to connect to the API. Please try again later.";
        chatOutput.scrollTop = messageGroup.offsetTop;
    }
});
