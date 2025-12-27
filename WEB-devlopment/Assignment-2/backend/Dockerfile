FROM node:18-alpine

# Set working directory to /usr/src/app (where backend code connects)
WORKDIR /usr/src/app

# Copy package files from the build context (root of project)
# Note: This assumes build context is the project root
COPY backend/package*.json ./

# Install dependencies
RUN npm install

# Copy backend source code
COPY backend/ .

# Copy public folder to the parent directory of app
COPY public ../public

# Expose port
EXPOSE 3000

# Start Application
CMD ["npm", "start"]
