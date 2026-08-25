// Japanese labels for API validation error fields, used to turn a
// `{ field: { rule: message } }` error map into one readable line. Needed
// wherever a form's fields are split across steps/tabs (EventWizard.vue) so
// a field-specific error isn't silently stuck on a step the user can't see.
const FIELD_LABELS = {
  name: '大会名',
  name_en: '大会名（英語）',
  slug: '大会スラッグ（URL）',
  subtitle: 'サブタイトル',
  organizer: '主催者',
  contact_email: 'お問い合わせメール',
  contact_info: '連絡先',
  start_at: '開始日時',
  end_at: '終了日時',
  registration_start_at: '申込開始日時',
  registration_end_at: '申込締切日時',
};

export function describeFieldErrors(fields) {
  return Object.entries(fields)
    .map(([field, errors]) => `${FIELD_LABELS[field] ?? field}: ${Object.values(errors)[0]}`)
    .join(' / ');
}
