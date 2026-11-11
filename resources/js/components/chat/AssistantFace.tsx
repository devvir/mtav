/** The Assistant: a small friendly house, the app's subject, looking at you */
export function AssistantFace({ size = 40 }: { size?: number }) {
  return (
    <svg className="assistant-face" viewBox="0 0 40 40" width={size} height={size} aria-hidden>
      <circle cx="20" cy="20" r="20" />
      <path
        className="assistant-house"
        d="M20 8.5 8.8 18.2c-.8.7-.3 2 .7 2H11v9.3c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-9.3h1.5c1 0 1.5-1.3.7-2z"
      />
      <circle className="assistant-eye" cx="16.3" cy="21.2" r="1.7" />
      <circle className="assistant-eye" cx="23.7" cy="21.2" r="1.7" />
      <path className="assistant-smile" d="M15.8 25.6c2.4 2.2 6 2.2 8.4 0" />
    </svg>
  );
}
