<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Auth;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = [];
        $perPage = 10;
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        if($request->startDate && $request->endDate){
            $userId = Auth::user()->id == 15 ? 79 : Auth::user()->id;
            $startDate = date('Y-m-d 00:00:00', strtotime($request->startDate));
            $endDate = date('Y-m-d 23:59:59', strtotime($request->endDate));
            
            $reports = DB::select("
                SELECT
                    down.idUpline,
                    down.idDevice,
                    down.name,
                    down.phone,
                    down.user_id,
                    down.user_name,
                    down.uuid,
                    u.name as user,
                    u.email,
                    send_reply.totSend,
                    send_reply.totReply,
                    first_send.byCustomer,
                    first_send.bySales,
                    first_send.timeReply,
                    contact.totContacts,
                    CASE
                        WHEN COALESCE(first_send.timeReply, 0) = 0 THEN 0.00
                        ELSE ROUND((COALESCE(first_send.timeReply, 0) / COALESCE(first_send.byCustomer+first_send.bySales, 1)) * 100, 2)
                    END AS minuteReplyPercent,
                    CASE
                        WHEN COALESCE(contact.totContacts, 0) = 0 THEN 0.00
                        ELSE ROUND((COALESCE(send_reply.totSend, 0) / COALESCE(contact.totContacts, 1)) * 100, 2)
                    END AS sendPercent,
                    CASE
                        WHEN COALESCE(send_reply.totSend, 0) = 0 THEN 0.00
                        ELSE ROUND((COALESCE(send_reply.totReply, 0) / COALESCE(send_reply.totSend, 1)) * 100, 2)
                    END AS replyPercent,
                    CASE
                        WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN 0.00
                        ELSE ROUND((COALESCE(first_send.byCustomer, 0) / COALESCE(contact.totContacts, 1)) * 100, 2)
                    END AS sendFirstCustomerPercent,
                    CASE
                        WHEN COALESCE(first_send.bySales, 0) = 0 THEN 0.00
                        ELSE ROUND((COALESCE(first_send.bySales, 0) / COALESCE(contact.totContacts, 1)) * 100, 2)
                    END AS sendFirstSalesPercent,
                    DATEDIFF(?, ?) + 1 AS totDays,
                    COUNT(*) OVER() as total_count
                FROM
                    (SELECT
                        dw.user_id AS idUpline,
                        dv.id AS idDevice,
                        dv.name,
                        dv.phone,
                        dv.user_id,
                        dv.user_name,
                        dv.uuid
                    FROM downlines dw
                    INNER JOIN devices dv ON dv.user_id = dw.downline_user_id
                    WHERE
                        dw.user_id = ?
                    ORDER BY dv.id DESC
                    ) down
                LEFT JOIN users u ON u.id = down.user_id
                LEFT JOIN (
                    SELECT
                        ch1.device_id,
                        SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0 END) AS totSend,
                        SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0 END) AS totReply
                    FROM
                        (SELECT
                            device_id, contact_id, fromMe
                        FROM chats
                        WHERE
                            created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                        GROUP BY device_id, contact_id, fromMe) ch1
                    GROUP BY ch1.device_id
                ) send_reply ON send_reply.device_id = down.idDevice
                LEFT JOIN (
                    SELECT
                        fsd.device_id,
                        COUNT(CASE WHEN fsd.firstSendBy = 'sales' THEN 1 END) AS bySales,
                        COUNT(CASE WHEN fsd.firstSendBy = 'customer' THEN 1 END) AS byCustomer,
                        SUM(fsd.timeReply) AS timeReply
                    FROM
                        (SELECT
                            contact_id,
                            device_id,
                            CASE
                                WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) <
                                    MIN(CASE WHEN fromMe = 'false' THEN created_at END) THEN 'sales'
                                WHEN MIN(CASE WHEN fromMe = 'false' THEN created_at END) <
                                    MIN(CASE WHEN fromMe = 'true' THEN created_at END) THEN 'customer'
                                WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) IS NOT NULL THEN 'sales'
                                ELSE 'customer'
                            END AS firstSendBy,
                            ABS(TIMESTAMPDIFF(MINUTE,
                                MIN(CASE WHEN fromMe = 'true' THEN created_at END),
                                MIN(CASE WHEN fromMe = 'false' THEN created_at END)
                            )) AS timeReply
                        FROM chats
                        WHERE
                            created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                        GROUP BY contact_id, device_id
                        ) fsd
                    GROUP BY fsd.device_id
                ) first_send ON first_send.device_id = down.idDevice
                LEFT JOIN (
                    SELECT
                        device_id,
                        COUNT(*) AS totContacts
                    FROM contacts
                    GROUP BY device_id
                ) contact ON contact.device_id = down.idDevice
                WHERE down.idDevice IS NOT NULL

                UNION ALL

                SELECT
                    down.idUpline,
                    down.idDevice,
                    down.name,
                    down.phone,
                    down.user_id,
                    down.user_name,
                    down.uuid,
                    u.name as user,
                    u.email,
                    send_reply.totSend,
                    send_reply.totReply,
                    first_send.byCustomer,
                    first_send.bySales,
                    first_send.timeReply,
                    contact.totContacts,
                    CASE
                        WHEN COALESCE(first_send.timeReply, 0) = 0 THEN 0.00
                        ELSE ROUND((COALESCE(first_send.timeReply, 0) / COALESCE(first_send.byCustomer+first_send.bySales, 1)) * 100, 2)
                    END AS minuteReplyPercent,
                    CASE
                        WHEN COALESCE(contact.totContacts, 0) = 0 THEN 0.00
                        ELSE ROUND((COALESCE(send_reply.totSend, 0) / COALESCE(contact.totContacts, 1)) * 100, 2)
                    END AS sendPercent,
                    CASE
                        WHEN COALESCE(send_reply.totSend, 0) = 0 THEN 0.00
                        ELSE ROUND((COALESCE(send_reply.totReply, 0) / COALESCE(send_reply.totSend, 1)) * 100, 2)
                    END AS replyPercent,
                    CASE
                        WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN 0.00
                        ELSE ROUND((COALESCE(first_send.byCustomer, 0) / COALESCE(contact.totContacts, 1)) * 100, 2)
                    END AS sendFirstCustomerPercent,
                    CASE
                        WHEN COALESCE(first_send.bySales, 0) = 0 THEN 0.00
                        ELSE ROUND((COALESCE(first_send.bySales, 0) / COALESCE(contact.totContacts, 1)) * 100, 2)
                    END AS sendFirstSalesPercent,
                    DATEDIFF(?, ?) + 1 AS totDays,
                    COUNT(*) OVER() as total_count
                FROM
                    (SELECT
                        NULL AS idUpline,
                        dv.id AS idDevice,
                        dv.name,
                        dv.phone,
                        dv.user_id,
                        dv.user_name,
                        dv.uuid
                    FROM devices dv
                    WHERE
                        dv.user_id = ?
                        AND dv.id NOT IN (
                            SELECT dv2.id FROM downlines dw2
                            INNER JOIN devices dv2 ON dv2.user_id = dw2.downline_user_id
                            WHERE dw2.user_id = ?
                        )
                    ORDER BY dv.id DESC
                    ) down
                LEFT JOIN users u ON u.id = down.user_id
                LEFT JOIN (
                    SELECT
                        ch1.device_id,
                        SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0 END) AS totSend,
                        SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0 END) AS totReply
                    FROM
                        (SELECT
                            device_id, contact_id, fromMe
                        FROM chats
                        WHERE
                            created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                        GROUP BY device_id, contact_id, fromMe) ch1
                    GROUP BY ch1.device_id
                ) send_reply ON send_reply.device_id = down.idDevice
                LEFT JOIN (
                    SELECT
                        fsd.device_id,
                        COUNT(CASE WHEN fsd.firstSendBy = 'sales' THEN 1 END) AS bySales,
                        COUNT(CASE WHEN fsd.firstSendBy = 'customer' THEN 1 END) AS byCustomer,
                        SUM(fsd.timeReply) AS timeReply
                    FROM
                        (SELECT
                            contact_id,
                            device_id,
                            CASE
                                WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) <
                                    MIN(CASE WHEN fromMe = 'false' THEN created_at END) THEN 'sales'
                                WHEN MIN(CASE WHEN fromMe = 'false' THEN created_at END) <
                                    MIN(CASE WHEN fromMe = 'true' THEN created_at END) THEN 'customer'
                                WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) IS NOT NULL THEN 'sales'
                                ELSE 'customer'
                            END AS firstSendBy,
                            ABS(TIMESTAMPDIFF(MINUTE,
                                MIN(CASE WHEN fromMe = 'true' THEN created_at END),
                                MIN(CASE WHEN fromMe = 'false' THEN created_at END)
                            )) AS timeReply
                        FROM chats
                        WHERE
                            created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                        GROUP BY contact_id, device_id
                        ) fsd
                    GROUP BY fsd.device_id
                ) first_send ON first_send.device_id = down.idDevice
                LEFT JOIN (
                    SELECT
                        device_id,
                        COUNT(*) AS totContacts
                    FROM contacts
                    GROUP BY device_id
                ) contact ON contact.device_id = down.idDevice
                WHERE down.idDevice IS NOT NULL
                
                LIMIT ? OFFSET ?
            ", [
                $endDate, $startDate,
                $userId,
                $startDate, $endDate,
                $startDate, $endDate,
                $endDate, $startDate,
                $userId, $userId,
                $startDate, $endDate,
                $startDate, $endDate,
                $perPage, $offset
            ]);

            $total = $reports[0]->total_count ?? 0;
            $reports = new LengthAwarePaginator(
                $reports,
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query()
                ]
            );
        };

        return view('user.report.index', compact('reports', 'request'));
    }

    public function export(Request $request)
    {
        if(!$request->startDate || !$request->endDate) {
            return response()->json([
                'message' => __('Please select date range'),
                'status' => 'error'
            ]);
        }

        $userId = Auth::user()->id == 15 ? 79 : Auth::user()->id;
        $startDate = date('Y-m-d 00:00:00', strtotime($request->startDate));
        $endDate = date('Y-m-d 23:59:59', strtotime($request->endDate));

        $reports = DB::select("
            SELECT
                down.idUpline,
                down.idDevice,
                down.name,
                down.phone,
                down.user_id,
                down.user_name,
                down.uuid,
                u.name as user,
                u.email,
                send_reply.totSend,
                send_reply.totReply,
                first_send.byCustomer,
                first_send.bySales,
                first_send.timeReply,
                contact.totContacts,
                CASE
                    WHEN COALESCE(first_send.timeReply, 0) = 0 THEN 0.00
                    ELSE ROUND((COALESCE(first_send.timeReply, 0) / COALESCE(first_send.byCustomer+first_send.bySales, 1)) * 100, 2)
                END AS minuteReplyPercent,
                CASE
                    WHEN COALESCE(contact.totContacts, 0) = 0 THEN 0.00
                    ELSE ROUND((COALESCE(send_reply.totSend, 0) / COALESCE(contact.totContacts, 1)) * 100, 2)
                END AS sendPercent,
                CASE
                    WHEN COALESCE(send_reply.totSend, 0) = 0 THEN 0.00
                    ELSE ROUND((COALESCE(send_reply.totReply, 0) / COALESCE(send_reply.totSend, 1)) * 100, 2)
                END AS replyPercent,
                CASE
                    WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN 0.00
                    ELSE ROUND((COALESCE(first_send.byCustomer, 0) / COALESCE(contact.totContacts, 1)) * 100, 2)
                END AS sendFirstCustomerPercent,
                CASE
                    WHEN COALESCE(first_send.bySales, 0) = 0 THEN 0.00
                    ELSE ROUND((COALESCE(first_send.bySales, 0) / COALESCE(contact.totContacts, 1)) * 100, 2)
                END AS sendFirstSalesPercent,
                DATEDIFF(?, ?) + 1 AS totDays
            FROM
                (SELECT
                    dw.user_id AS idUpline,
                    dv.id AS idDevice,
                    dv.name,
                    dv.phone,
                    dv.user_id,
                    dv.user_name,
                    dv.uuid
                FROM downlines dw
                INNER JOIN devices dv ON dv.user_id = dw.downline_user_id
                WHERE
                    dw.user_id = ?
                ORDER BY dv.id DESC
                ) down
            LEFT JOIN users u ON u.id = down.user_id
            LEFT JOIN (
                SELECT
                    ch1.device_id,
                    SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0 END) AS totSend,
                    SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0 END) AS totReply
                FROM
                    (SELECT
                        device_id, contact_id, fromMe
                    FROM chats
                    WHERE
                        created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                    GROUP BY device_id, contact_id, fromMe) ch1
                GROUP BY ch1.device_id
            ) send_reply ON send_reply.device_id = down.idDevice
            LEFT JOIN (
                SELECT
                    fsd.device_id,
                    COUNT(CASE WHEN fsd.firstSendBy = 'sales' THEN 1 END) AS bySales,
                    COUNT(CASE WHEN fsd.firstSendBy = 'customer' THEN 1 END) AS byCustomer,
                    SUM(fsd.timeReply) AS timeReply
                FROM
                    (SELECT
                        contact_id,
                        device_id,
                        CASE
                            WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) <
                                MIN(CASE WHEN fromMe = 'false' THEN created_at END) THEN 'sales'
                            WHEN MIN(CASE WHEN fromMe = 'false' THEN created_at END) <
                                MIN(CASE WHEN fromMe = 'true' THEN created_at END) THEN 'customer'
                            WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) IS NOT NULL THEN 'sales'
                            ELSE 'customer'
                        END AS firstSendBy,
                        ABS(TIMESTAMPDIFF(MINUTE,
                            MIN(CASE WHEN fromMe = 'true' THEN created_at END),
                            MIN(CASE WHEN fromMe = 'false' THEN created_at END)
                        )) AS timeReply
                    FROM chats
                    WHERE
                        created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                    GROUP BY contact_id, device_id
                    ) fsd
                GROUP BY fsd.device_id
            ) first_send ON first_send.device_id = down.idDevice
            LEFT JOIN (
                SELECT
                    device_id,
                    COUNT(*) AS totContacts
                FROM contacts
                GROUP BY device_id
            ) contact ON contact.device_id = down.idDevice
            WHERE down.idDevice IS NOT NULL

            UNION ALL

            SELECT
                down.idUpline,
                down.idDevice,
                down.name,
                down.phone,
                down.user_id,
                down.user_name,
                down.uuid,
                u.name as user,
                u.email,
                send_reply.totSend,
                send_reply.totReply,
                first_send.byCustomer,
                first_send.bySales,
                first_send.timeReply,
                contact.totContacts,
                CASE
                    WHEN COALESCE(first_send.timeReply, 0) = 0 THEN 0.00
                    ELSE ROUND((COALESCE(first_send.timeReply, 0) / COALESCE(first_send.byCustomer+first_send.bySales, 1)) * 100, 2)
                END AS minuteReplyPercent,
                CASE
                    WHEN COALESCE(contact.totContacts, 0) = 0 THEN 0.00
                    ELSE ROUND((COALESCE(send_reply.totSend, 0) / COALESCE(contact.totContacts, 1)) * 100, 2)
                END AS sendPercent,
                CASE
                    WHEN COALESCE(send_reply.totSend, 0) = 0 THEN 0.00
                    ELSE ROUND((COALESCE(send_reply.totReply, 0) / COALESCE(send_reply.totSend, 1)) * 100, 2)
                END AS replyPercent,
                CASE
                    WHEN COALESCE(first_send.byCustomer, 0) = 0 THEN 0.00
                    ELSE ROUND((COALESCE(first_send.byCustomer, 0) / COALESCE(contact.totContacts, 1)) * 100, 2)
                END AS sendFirstCustomerPercent,
                CASE
                    WHEN COALESCE(first_send.bySales, 0) = 0 THEN 0.00
                    ELSE ROUND((COALESCE(first_send.bySales, 0) / COALESCE(contact.totContacts, 1)) * 100, 2)
                END AS sendFirstSalesPercent,
                DATEDIFF(?, ?) + 1 AS totDays
            FROM
                (SELECT
                    NULL AS idUpline,
                    dv.id AS idDevice,
                    dv.name,
                    dv.phone,
                    dv.user_id,
                    dv.user_name,
                    dv.uuid
                FROM devices dv
                WHERE
                    dv.user_id = ?
                    AND dv.id NOT IN (
                        SELECT dv2.id FROM downlines dw2
                        INNER JOIN devices dv2 ON dv2.user_id = dw2.downline_user_id
                        WHERE dw2.user_id = ?
                    )
                ORDER BY dv.id DESC
                ) down
            LEFT JOIN users u ON u.id = down.user_id
            LEFT JOIN (
                SELECT
                    ch1.device_id,
                    SUM(CASE WHEN ch1.fromMe = 'true' THEN 1 ELSE 0 END) AS totSend,
                    SUM(CASE WHEN ch1.fromMe = 'false' THEN 1 ELSE 0 END) AS totReply
                FROM
                    (SELECT
                        device_id, contact_id, fromMe
                    FROM chats
                    WHERE
                        created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                    GROUP BY device_id, contact_id, fromMe) ch1
                GROUP BY ch1.device_id
            ) send_reply ON send_reply.device_id = down.idDevice
            LEFT JOIN (
                SELECT
                    fsd.device_id,
                    COUNT(CASE WHEN fsd.firstSendBy = 'sales' THEN 1 END) AS bySales,
                    COUNT(CASE WHEN fsd.firstSendBy = 'customer' THEN 1 END) AS byCustomer,
                    SUM(fsd.timeReply) AS timeReply
                FROM
                    (SELECT
                        contact_id,
                        device_id,
                        CASE
                            WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) <
                                MIN(CASE WHEN fromMe = 'false' THEN created_at END) THEN 'sales'
                            WHEN MIN(CASE WHEN fromMe = 'false' THEN created_at END) <
                                MIN(CASE WHEN fromMe = 'true' THEN created_at END) THEN 'customer'
                            WHEN MIN(CASE WHEN fromMe = 'true' THEN created_at END) IS NOT NULL THEN 'sales'
                            ELSE 'customer'
                        END AS firstSendBy,
                        ABS(TIMESTAMPDIFF(MINUTE,
                            MIN(CASE WHEN fromMe = 'true' THEN created_at END),
                            MIN(CASE WHEN fromMe = 'false' THEN created_at END)
                        )) AS timeReply
                    FROM chats
                    WHERE
                        created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
                    GROUP BY contact_id, device_id
                    ) fsd
                GROUP BY fsd.device_id
            ) first_send ON first_send.device_id = down.idDevice
            LEFT JOIN (
                SELECT
                    device_id,
                    COUNT(*) AS totContacts
                FROM contacts
                GROUP BY device_id
            ) contact ON contact.device_id = down.idDevice
            WHERE down.idDevice IS NOT NULL
        ", [
            $endDate, $startDate,
            $userId,
            $startDate, $endDate,
            $startDate, $endDate,
            $endDate, $startDate,
            $userId, $userId,
            $startDate, $endDate,
            $startDate, $endDate
        ]);

        $filename = 'reports_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($reports) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for proper Excel encoding
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add headers
            fputcsv($file, [
                'Account/Downline Name',
                'Device Name',
                'Phone',
                'Total Days',
                'Total Contacts',
                'Total Send',
                'Send Rate (%)',
                'Total Sales Initiated Send',
                'Sales Initiated Send Rate (%)',
                'Total Customer Initiated Send',
                'Customer Initiated Send Rate (%)',
                'Total Reply',
                'Reply Rate (%)',
                'Average Reply Time'
            ]);

            // Add data
            foreach ($reports as $report) {
                $time = \Carbon\Carbon::createFromTimestampUTC(($report->minuteReplyPercent / 100) * 60);
                fputcsv($file, [
                    $report->user,
                    $report->name,
                    $report->phone,
                    $report->totDays,
                    $report->totContacts,
                    $report->totSend,
                    $report->sendPercent,
                    $report->bySales,
                    $report->sendFirstSalesPercent,
                    $report->byCustomer,
                    $report->sendFirstCustomerPercent,
                    $report->totReply,
                    $report->replyPercent,
                    $time->hour . 'h ' . $time->minute . 'm ' . $time->second . 's'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}