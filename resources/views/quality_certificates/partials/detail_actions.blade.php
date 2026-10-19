@can('certificate.view')
                        @if(!auth()->user()->hasRole('TrungTam') || ($qualityCertificate->signed_at && in_array($qualityCertificate->status, ['ISSUED', 'REVOKED'], true) && $qualityCertificate->pades_status === 'SIGNED_PDF' && filled($qualityCertificate->pdf_path)))
                        <a href="{{ route('quality-certificates.pdf', $qualityCertificate) }}" target="_blank" class="btn btn-info">
                            <i class="fas fa-file-pdf"></i> {{ $qualityCertificate->signed_at ? 'Xem PDF đã ký' : 'Xem trước PDF' }}
                        </a>
                        @endif
                    @endcan

                    @if ($qualityCertificate->signed_at)
                        



                        @can('certificate.print')
                            @if($qualityCertificate->status !== 'REVOKED')
                                @if($qualityCertificate->signed_at)
                                    <button type="button"
                                            class="btn btn-warning js-print-hard-copy"
                                            data-toggle="modal"
                                            data-target="#printHardCopyModal"
                                            data-template="single"
                                            data-mode="normal"
                                            data-label="In đơn"
                                            data-description="In theo mẫu ký tươi hiện tại, giữ vùng ghi chú/chữ ký cố định trên tất cả các trang.">
                                        <i class="fas fa-print"></i> In đơn
                                    </button>
                                    <button type="button"
                                            class="btn btn-info js-print-hard-copy"
                                            data-toggle="modal"
                                            data-target="#printHardCopyModal"
                                            data-template="batch"
                                            data-mode="normal"
                                            data-label="In bộ"
                                            data-description="In theo mẫu phân trang mới, các trang trước tận dụng diện tích bảng và chỉ chừa khoảng ký ở trang cuối.">
                                        <i class="fas fa-layer-group"></i> In bộ
                                    </button>
                                @elseif($canEmergencyPrintHardCopy)
                                    <button type="button"
                                            class="btn btn-outline-warning js-print-hard-copy"
                                            data-toggle="modal"
                                            data-target="#printHardCopyModal"
                                            data-template="single"
                                            data-mode="emergency"
                                            data-label="In đơn khẩn cấp"
                                            data-description="Phiếu chưa ký số. Chỉ dùng khi SmartCA lỗi/quá hạn hoặc khách hàng cần gấp và phiếu đã vào bước chờ ký số. Thao tác sẽ được lưu lịch sử in khẩn cấp.">
                                        <i class="fas fa-print"></i> In đơn khẩn cấp
                                    </button>
                                    <button type="button"
                                            class="btn btn-outline-info js-print-hard-copy"
                                            data-toggle="modal"
                                            data-target="#printHardCopyModal"
                                            data-template="batch"
                                            data-mode="emergency"
                                            data-label="In bộ khẩn cấp"
                                            data-description="Phiếu chưa ký số. In theo mẫu phân trang mới và chỉ chừa khoảng ký ở trang cuối. Thao tác sẽ được lưu lịch sử in khẩn cấp.">
                                        <i class="fas fa-layer-group"></i> In bộ khẩn cấp
                                    </button>
                                @endif
                            @endif
                        @endcan

                        @if((auth()->user()->can('certificate.email') && $qualityCertificate->status !== 'REVOKED') || (auth()->user()->can('request.create') && $qualityCertificate->canRequestReissue()))
<details class="workflow-secondary-actions">
    <summary class="btn btn-outline-secondary"><i class="fas fa-ellipsis-h"></i> Thao tác khác</summary>
    <div class="workflow-secondary-actions-body">
@can('certificate.email')
                            @if($qualityCertificate->status !== 'REVOKED')
                                <form action="{{ route('quality-certificates.resend-email', $qualityCertificate) }}" method="POST"
                                      class="d-inline"
                                      data-loading-lock
                                      data-loading-message="Đang gửi lại email phiếu CNCL, vui lòng chờ..."
                                      onsubmit="if (!confirm('Gửi lại email phiếu CNCL cho Trung tâm phân phối?')) return false; window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-envelope"></i> Gửi lại email
                                    </button>
                                </form>
                            @endif
                        @endcan
@can('request.create')
                            @if($qualityCertificate->canRequestReissue())
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#reissueModal">
                                    <i class="fas fa-redo"></i> Yêu cầu cấp lại
                                </button>
                            @endif
                        @endcan
    </div>
</details>
@endif
                    @else
                        @can('certificate.sign')
                            @if ($qualityCertificate->status === 'REJECTED')
                                @unless($canReturnRejectedCertificateRequestToDvkh)
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-undo"></i> Phiếu đã trả lại
                                    </button>
                                @endunless
                            @elseif (in_array($qualityCertificate->smartca_status, ['PENDING', 'EXPIRED'], true) && $qualityCertificate->smartca_transaction_id)
                                <form action="{{ route('quality-certificates.smartca-status', $qualityCertificate) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message'))"
                                      data-loading-lock
                                      data-loading-message="Đang kiểm tra kết quả ký VNPT SmartCA và gửi email nếu ký thành công. Vui lòng chờ...">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-sync"></i> Kiểm tra kết quả ký
                                    </button>
                                </form>
                                @if($smartCaCanResend)
                                    <form action="{{ route('quality-certificates.sign', $qualityCertificate) }}" method="POST"
                                          class="d-inline"
                                          data-loading-lock
                                          data-loading-message="Đang gửi lại yêu cầu ký sang VNPT SmartCA. Vui lòng chờ..."
                                          onsubmit="if (!confirm('Gửi lại yêu cầu ký phiếu này sang VNPT SmartCA? Hệ thống sẽ kiểm tra giao dịch cũ trước khi gửi lại.')) return false; window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
                                        @csrf
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-file-signature"></i> Gửi lại yêu cầu ký
                                        </button>
                                    </form>
                                @endif
                            @else
                                @if($canApproveForSigning)
                                    <form action="{{ route('quality-certificates.approve-for-signing', $qualityCertificate) }}"
                                          method="POST"
                                          class="d-inline"
                                          data-loading-lock
                                          data-loading-message="Đang đưa phiếu vào danh sách chờ gửi ký. Vui lòng chờ..."
                                          onsubmit="window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Đưa vào chờ gửi ký
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('quality-certificates.sign', $qualityCertificate) }}" method="POST"
                                      class="d-inline"
                                      data-loading-lock
                                      data-loading-message="Đang gửi yêu cầu ký sang VNPT SmartCA. Vui lòng chờ..."
                                      onsubmit="if (!confirm('{{ $qualityCertificate->isAwaitingManagerApproval() ? 'Gửi ký trực tiếp phiếu này? Thao tác này đồng thời xác nhận Trưởng PTN đã duyệt nội dung.' : ($smartCaCanResend ? 'Gửi lại yêu cầu ký phiếu này sang VNPT SmartCA?' : 'Gửi yêu cầu ký phiếu này sang VNPT SmartCA?') }}')) return false; window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
                                    @csrf
                                    <button type="submit" class="btn {{ $smartCaCanResend ? 'btn-warning' : 'btn-success' }}" {{ $canSendSignature ? '' : 'disabled' }}>
                                        <i class="fas fa-file-signature"></i> {{ $qualityCertificate->isAwaitingManagerApproval() ? 'Duyệt và gửi ký SmartCA' : ($smartCaCanResend ? 'Gửi lại yêu cầu ký' : 'Gửi yêu cầu ký SmartCA') }}
                                    </button>
                                </form>
                            @endif
                        @endcan

                        @can('ptn.process')
                            @if($canReturnRejectedCertificateRequestToDvkh)
                                <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#returnRejectedToDvkhModal">
                                    <i class="fas fa-undo"></i> Trả lại DVKH
                                </button>
                            @endif
                        @endcan

                        @can('certificate.reject')
                            @if($qualityCertificate->status !== 'REJECTED' && ($qualityCertificate->smartca_status !== 'PENDING' || $smartCaPendingExpired))
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectSignatureModal">
                                    <i class="fas fa-undo"></i> Từ chối ký
                                </button>
                            @endif
                        @endcan
                    @endif
