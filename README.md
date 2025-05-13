# 🏋️‍♂️ GymAI – Symfony Gym Website with Nutrition AI Assistant

**GymAI** is a simple and modern Symfony web application for a gym platform that helps users get fitness and nutrition advice through an integrated AI assistant. It allows users to register, log in, explore gym subscription offers, and chat with a nutrition-focused AI that provides personalized tips and guidance.

---

## 🚀 Features

- 🔐 User Sign Up & Login (using SQLite)
- 🧭 Responsive Navbar to navigate through the site
- 🏠 Home Page with gym subscription programs
- ℹ️ About Page describing the project and its creators
- 🤖 AI Assistant Page to ask nutrition-related questions
- 💬 Chat History that saves previous questions & answers
- 🗑️ Optional: Delete individual chat messages

---

## 🛠️ Tech Stack

- **Backend**: Symfony 6
- **Database**: SQLite
- **Frontend**: Twig (with Bootstrap or custom CSS)
- **AI Integration**: [aimlapi.com](https://aimlapi.com)

---

## 🏁 Getting Started

### Prerequisites

- PHP 8.1+
- Composer
- Symfony CLI (optional but recommended)

### Installation Steps

```bash
# Clone the repository
git clone https://github.com/your-username/gymai.git
cd gymai

# Install dependencies
composer install

# Copy and configure environment file
cp .env.example .env
# Set your AI API key in the .env file

# Start the Symfony server
symfony server:start
````
🔑 API Key Setup
To use the AI Assistant, you need an API key from aimlapi.com.

In the .env file, set:
````bash
AI_API_KEY=your-api-key-here
````
## 📚 Usage
✅ Sign up at /signup

✅ Log in at /login

✅ Ask questions to the AI at /ai

✅ View chat history on the AI page

✅ Delete chat messages (if enabled)


## 🙌 Contributors
[Mohamed Yazid Hammadi] – Developer

## 📄 License
This project is open-source and available under the MIT License.

