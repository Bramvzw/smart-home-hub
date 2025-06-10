module.exports = {
  testEnvironment: 'jsdom',
  moduleFileExtensions: ['js', 'json'],
  transform: {
    '^.+\\.js$': 'babel-jest',
  },
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/$1',
  },
  testMatch: [
    '**/tests/js/**/*.spec.js',
  ],
    setupFilesAfterEnv: ['<rootDir>/tests/js/setup.js'],
};
