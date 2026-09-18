export type ClubInfo = {
    name: string;
    logo: string;
    cover: string | null;
    description: string | null;
    home_venue: string | null;
    founded_year: string | null;
    facebook_url: string | null;
    contact_email: string | null;
};

export type HomeArticle = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    category: string;
    category_label: string;
    cover: string | null;
    published_at: string | null;
};

export type HomeMatch = {
    id: number;
    title: string;
    opponent: string;
    venue: string | null;
    is_home: boolean;
    kickoff_at: string;
};

export type HomePlayerStat = {
    id: number;
    name: string;
    jersey_number: number | null;
    photo: string | null;
    value: number;
};

export type ClubOption = {
    value: string;
    label: string;
};

export type ClubMember = {
    id: number;
    name: string;
    nickname: string | null;
    display_name: string;
    jersey_number: number | null;
    preferred_position: string | null;
    preferred_position_label: string | null;
    photo_path: string | null;
    photo: string | null;
    phone: string | null;
    date_of_birth: string | null;
    joined_at: string | null;
    status: string;
    status_label: string;
    role: string;
    role_label: string;
    bio: string | null;
};

export type ClubMatch = {
    id: number;
    title: string | null;
    display_title: string;
    opponent: string;
    venue: string | null;
    is_home: boolean;
    kickoff_at: string;
    kickoff_input: string;
    score_for: number | null;
    score_against: number | null;
    status: string;
    status_label: string;
    mvp_member_id: number | null;
    mvp_name: string | null;
    notes: string | null;
    attending_count: number;
    absent_count: number;
    pending_count: number;
};

export type ClubDashboardStats = {
    members_total: number;
    members_active: number;
    upcoming_matches: number;
    matches_this_month: number;
    pending_attendance: number;
};

export type ClubPitchSlot = {
    value: string;
    label: string;
    group_label: string;
    is_starter: boolean;
    top: string | null;
    left: string | null;
};

export type ClubMatchLineup = {
    id: number;
    member_id: number;
    member_name: string | null;
    photo: string | null;
    jersey_number: number | null;
    slot: string;
    slot_label: string;
    is_starter: boolean;
    rating: number | null;
    top: string | null;
    left: string | null;
};

export type ClubMatchGoal = {
    id: number;
    member_id: number;
    member_name: string | null;
    minute: number | null;
};

export type ClubAttendanceRow = {
    id: number;
    member_id: number;
    member_name: string;
    jersey_number: number | null;
    status: string;
    note: string | null;
};

export type ClubFundContribution = {
    id: number;
    group_key: string | null;
    member_id: number;
    member_name: string | null;
    title: string | null;
    amount: number;
    status: string;
    status_label: string;
    paid_at: string | null;
    note: string | null;
};

export type ClubFundGroup = {
    group_key: string | null;
    title: string;
    amount: number;
    amount_min: number;
    amount_max: number;
    paid_count: number;
    unpaid_count: number;
    contributions: ClubFundContribution[];
};

export type ClubFundSummary = {
    paid_total: number;
    unpaid_total: number;
    paid_count: number;
    unpaid_count: number;
    expense_total: number;
    expense_count: number;
    expense_this_month: number;
    balance: number;
};

export type ClubExpense = {
    id: number;
    title: string;
    category: string;
    category_label: string;
    amount: number;
    spent_at: string;
    note: string | null;
};

export type ClubExpenseGroup = {
    month_key: string;
    month_label: string;
    total: number;
    expenses: ClubExpense[];
};

export type ClubKitPurchase = {
    id: number;
    member_id: number;
    member_name: string | null;
    amount: number;
    status: string;
    status_label: string;
    paid_at: string | null;
    note: string | null;
};
