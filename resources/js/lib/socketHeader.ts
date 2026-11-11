/**
 * This page's connection to the websocket server, named on each request it makes
 * (X-Socket-ID): the server announces what the page changes to every page but itself.
 */
let socketId: () => string | undefined = () => undefined;

export const nameSocket = (current: () => string | undefined) => {
  socketId = current;
};

export const socketHeader = (): Record<string, string> => {
  const id = socketId();
  return id ? { 'X-Socket-ID': id } : {};
};
