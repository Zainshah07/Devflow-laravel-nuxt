# Use official Node.js LTS Alpine image
FROM node:22-alpine

# Set working directory
WORKDIR /app

# Copy only package.json and package-lock.json first for caching
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy the rest of the app
COPY . .

# Expose Nuxt dev port
EXPOSE 3000

# Command to run Nuxt in dev mode, accessible from host
CMD ["npm", "run", "dev", "--", "--host"]