/** Matches the text that a viewer reads. Views hold hidden text, such as error panels and phone-width variants. */
export const VISIBLE_TEXT = { useInnerText: true };

/** The name of a Pick's player as the screens show it. */
export const fullName = (pick: { firstName: string; lastName: string }) => `${pick.firstName} ${pick.lastName}`;
