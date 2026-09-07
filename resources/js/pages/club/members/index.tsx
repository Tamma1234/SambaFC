import { Form, Head, usePage } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import ClubPage from '@/components/club-page';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes';
import {
    destroy,
    index as membersIndex,
    store,
    update,
} from '@/routes/club/members';
import type { ClubMember, ClubOption } from '@/types';

type Props = {
    members: ClubMember[];
    positions: ClubOption[];
    statuses: ClubOption[];
    roles: ClubOption[];
};

export default function MembersIndex({
    members,
    positions,
    statuses,
    roles,
}: Props) {
    const slug = usePage().props.currentTeam?.slug ?? '';
    const [editing, setEditing] = useState<ClubMember | null>(null);
    const [creating, setCreating] = useState(false);
    const [deleting, setDeleting] = useState<ClubMember | null>(null);

    return (
        <ClubPage>
            <Head title="Thành viên" />

            <div className="flex items-center justify-between gap-4">
                <Heading
                    variant="small"
                    title="Thành viên"
                    description="Thêm, sửa và xóa cầu thủ trong đội."
                />
                <Button onClick={() => setCreating(true)}>
                    <Plus />
                    Thêm thành viên
                </Button>
            </div>

            <div className="overflow-hidden rounded-xl border">
                <table className="w-full text-left text-sm">
                    <thead className="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th className="px-4 py-3 font-medium">STT</th>
                            <th className="px-4 py-3 font-medium">Ảnh</th>
                            <th className="px-4 py-3 font-medium">Số áo</th>
                            <th className="px-4 py-3 font-medium">Tên</th>
                            <th className="px-4 py-3 font-medium">Vị trí</th>
                            <th className="px-4 py-3 font-medium">Vai trò</th>
                            <th className="px-4 py-3 font-medium">
                                Trạng thái
                            </th>
                            <th className="px-4 py-3 font-medium" />
                        </tr>
                    </thead>
                    <tbody>
                        {members.map((member, index) => (
                            <tr
                                key={member.id}
                                className="border-t"
                                data-test="member-row"
                            >
                                <td className="text-muted-foreground px-4 py-3">
                                    {index + 1}
                                </td>
                                <td className="px-4 py-3">
                                    {member.photo ? (
                                        <img
                                            src={member.photo}
                                            alt={member.display_name}
                                            className="size-10 rounded-md object-cover"
                                        />
                                    ) : (
                                        <div className="bg-muted text-muted-foreground flex size-10 items-center justify-center rounded-md text-xs font-medium">
                                            {member.display_name.slice(0, 1)}
                                        </div>
                                    )}
                                </td>
                                <td className="px-4 py-3 font-medium">
                                    {member.jersey_number ?? '—'}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="font-medium">
                                        {member.display_name}
                                    </div>
                                    {member.nickname ? (
                                        <div className="text-muted-foreground text-xs">
                                            {member.name}
                                        </div>
                                    ) : null}
                                </td>
                                <td className="px-4 py-3">
                                    {member.preferred_position_label ?? '—'}
                                </td>
                                <td className="px-4 py-3">
                                    {member.role_label}
                                </td>
                                <td className="px-4 py-3">
                                    <Badge
                                        variant={
                                            member.status === 'active'
                                                ? 'default'
                                                : 'outline'
                                        }
                                    >
                                        {member.status_label}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => setEditing(member)}
                                        >
                                            <Pencil />
                                            <span className="sr-only">Sửa</span>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => setDeleting(member)}
                                        >
                                            <Trash2 />
                                            <span className="sr-only">Xóa</span>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {members.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={8}
                                    className="text-muted-foreground px-4 py-10 text-center"
                                >
                                    Chưa có thành viên. Hãy thêm cầu thủ đầu
                                    tiên.
                                </td>
                            </tr>
                        ) : null}
                    </tbody>
                </table>
            </div>

            <MemberFormDialog
                open={creating}
                onOpenChange={setCreating}
                slug={slug}
                positions={positions}
                statuses={statuses}
                roles={roles}
            />

            <MemberFormDialog
                open={editing !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditing(null);
                    }
                }}
                slug={slug}
                member={editing}
                positions={positions}
                statuses={statuses}
                roles={roles}
            />

            <Dialog
                open={deleting !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleting(null);
                    }
                }}
            >
                <DialogContent>
                    {deleting ? (
                        <Form
                            key={deleting.id}
                            {...destroy.form([slug, deleting.id])}
                            onSuccess={() => setDeleting(null)}
                        >
                            {({ processing }) => (
                                <>
                                    <DialogHeader>
                                        <DialogTitle>
                                            Xóa thành viên?
                                        </DialogTitle>
                                        <DialogDescription>
                                            {deleting.display_name} sẽ bị xóa
                                            khỏi danh sách đội.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <DialogFooter>
                                        <DialogClose asChild>
                                            <Button variant="secondary">
                                                Hủy
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            disabled={processing}
                                        >
                                            Xóa
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    ) : null}
                </DialogContent>
            </Dialog>
        </ClubPage>
    );
}

function MemberFormDialog({
    open,
    onOpenChange,
    slug,
    member,
    positions,
    statuses,
    roles,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    slug: string;
    member?: ClubMember | null;
    positions: ClubOption[];
    statuses: ClubOption[];
    roles: ClubOption[];
}) {
    const formProps = member
        ? update.form([slug, member.id])
        : store.form(slug);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <Form
                    key={`${member?.id ?? 'create'}-${String(open)}`}
                    {...formProps}
                    encType="multipart/form-data"
                    className="space-y-4"
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>
                                    {member
                                        ? 'Sửa thành viên'
                                        : 'Thêm thành viên'}
                                </DialogTitle>
                                <DialogDescription>
                                    Thông tin cầu thủ sẽ hiện trên trang thành
                                    viên của CLB.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="name">Họ tên</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        defaultValue={member?.name ?? ''}
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="nickname">Biệt danh</Label>
                                    <Input
                                        id="nickname"
                                        name="nickname"
                                        defaultValue={member?.nickname ?? ''}
                                    />
                                    <InputError message={errors.nickname} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="jersey_number">Số áo</Label>
                                    <Input
                                        id="jersey_number"
                                        name="jersey_number"
                                        type="number"
                                        min={0}
                                        max={99}
                                        defaultValue={
                                            member?.jersey_number ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.jersey_number}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="preferred_position">
                                        Vị trí
                                    </Label>
                                    <NativeSelect
                                        id="preferred_position"
                                        name="preferred_position"
                                        defaultValue={
                                            member?.preferred_position ?? ''
                                        }
                                    >
                                        <option value="">Chưa chọn</option>
                                        {positions.map((position) => (
                                            <option
                                                key={position.value}
                                                value={position.value}
                                            >
                                                {position.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError
                                        message={errors.preferred_position}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Điện thoại</Label>
                                    <Input
                                        id="phone"
                                        name="phone"
                                        defaultValue={member?.phone ?? ''}
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="date_of_birth">
                                        Ngày sinh
                                    </Label>
                                    <Input
                                        id="date_of_birth"
                                        name="date_of_birth"
                                        type="date"
                                        defaultValue={
                                            member?.date_of_birth ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.date_of_birth}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="joined_at">
                                        Ngày gia nhập
                                    </Label>
                                    <Input
                                        id="joined_at"
                                        name="joined_at"
                                        type="date"
                                        defaultValue={member?.joined_at ?? ''}
                                    />
                                    <InputError message={errors.joined_at} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="role">Vai trò</Label>
                                    <NativeSelect
                                        id="role"
                                        name="role"
                                        defaultValue={member?.role ?? 'player'}
                                    >
                                        {roles.map((role) => (
                                            <option
                                                key={role.value}
                                                value={role.value}
                                            >
                                                {role.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError message={errors.role} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="status">Trạng thái</Label>
                                    <NativeSelect
                                        id="status"
                                        name="status"
                                        defaultValue={
                                            member?.status ?? 'active'
                                        }
                                    >
                                        {statuses.map((status) => (
                                            <option
                                                key={status.value}
                                                value={status.value}
                                            >
                                                {status.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError message={errors.status} />
                                </div>
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="photo">
                                        Ảnh thành viên
                                    </Label>
                                    {member?.photo ? (
                                        <img
                                            src={member.photo}
                                            alt={member.display_name}
                                            className="size-20 rounded-md object-cover"
                                        />
                                    ) : null}
                                    <Input
                                        id="photo"
                                        name="photo"
                                        type="file"
                                        accept="image/jpeg,image/png,image/gif,image/webp"
                                    />
                                    <p className="text-muted-foreground text-xs">
                                        Chọn file ảnh (JPG, PNG, GIF, WEBP), tối
                                        đa 5MB.
                                    </p>
                                    <InputError message={errors.photo} />
                                </div>
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="bio">Ghi chú</Label>
                                    <Textarea
                                        id="bio"
                                        name="bio"
                                        defaultValue={member?.bio ?? ''}
                                    />
                                    <InputError message={errors.bio} />
                                </div>
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button variant="secondary">Hủy</Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {member ? 'Lưu' : 'Thêm'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

MembersIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Bảng điều khiển',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Thành viên',
            href: props.currentTeam
                ? membersIndex(props.currentTeam.slug)
                : '/',
        },
    ],
});
