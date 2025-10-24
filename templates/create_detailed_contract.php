<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء عقد مفصل - نظام إدارة العقود سما</title>
    <link rel="stylesheet" href="/assets/css/unified-theme.css">
    <style>
        .detailed-contract-form {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .form-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c5530;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2c5530;
            display: flex;
            align-items: center;
        }

        .section-icon {
            width: 24px;
            height: 24px;
            margin-left: 10px;
            background: #2c5530;
            border-radius: 50%;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c5530;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(44, 85, 48, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2c5530;
            box-shadow: 0 0 15px rgba(44, 85, 48, 0.3);
            background: rgba(255, 255, 255, 0.95);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .contract-type-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .contract-type-option {
            position: relative;
        }

        .contract-type-option input[type="radio"] {
            display: none;
        }

        .contract-type-option label {
            display: block;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(44, 85, 48, 0.2);
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .contract-type-option input[type="radio"]:checked + label {
            background: rgba(44, 85, 48, 0.2);
            border-color: #2c5530;
            color: #2c5530;
            font-weight: bold;
        }

        .template-preview {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Amiri', serif;
            line-height: 1.8;
        }

        .btn-generate-preview {
            background: linear-gradient(135deg, #4a7c59 0%, #2c5530 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            margin: 10px 5px;
            transition: all 0.3s ease;
        }

        .btn-generate-preview:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 85, 48, 0.3);
        }

        .submit-section {
            text-align: center;
            padding: 30px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            margin-top: 30px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin: 0 10px;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(44, 85, 48, 0.4);
        }

        .btn-cancel {
            background: linear-gradient(135deg, #666 0%, #888 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 25px;
            font-size: 18px;
            cursor: pointer;
            margin: 0 10px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(102, 102, 102, 0.4);
        }

        .required {
            color: #e74c3c;
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .contract-type-selector {
                grid-template-columns: 1fr;
            }
            
            .detailed-contract-form {
                padding: 10px;
            }
            
            .form-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="detailed-contract-form">
            <div class="page-header">
                <h1>إنشاء عقد مفصل جديد</h1>
                <p>أدخل جميع تفاصيل العقد لإنشاء وثيقة شاملة ومهنية</p>
            </div>

            <form method="POST" action="/contracts/create-detailed" id="detailedContractForm">
                <!-- نوع العقد -->
                <div class="form-section">
                    <h3 class="section-title">
                        <span class="section-icon"></span>
                        نوع العقد
                    </h3>
                    
                    <div class="contract-type-selector">
                        <div class="contract-type-option">
                            <input type="radio" id="real_estate_speculation" name="contract_type" value="real_estate_speculation" checked>
                            <label for="real_estate_speculation">
                                <strong>عقد مضاربة عقارية</strong><br>
                                <small>للاستثمار العقاري والمضاربة</small>
                            </label>
                        </div>
                        <div class="contract-type-option">
                            <input type="radio" id="partnership" name="contract_type" value="partnership">
                            <label for="partnership">
                                <strong>عقد شراكة</strong><br>
                                <small>للشراكات التجارية</small>
                            </label>
                        </div>
                        <div class="contract-type-option">
                            <input type="radio" id="investment" name="contract_type" value="investment">
                            <label for="investment">
                                <strong>عقد استثمار</strong><br>
                                <small>للاستثمارات العامة</small>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- معلومات العقد الأساسية -->
                <div class="form-section">
                    <h3 class="section-title">
                        <span class="section-icon"></span>
                        معلومات العقد الأساسية
                    </h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title">عنوان العقد <span class="required">*</span></label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="hijri_date">التاريخ الهجري</label>
                            <input type="text" id="hijri_date" name="hijri_date" placeholder="مثال: 12-2-1447هـ">
                        </div>
                        
                        <div class="form-group">
                            <label for="location">مكان التوقيع</label>
                            <input type="text" id="location" name="location" placeholder="مثال: محافظة جدة">
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date">تاريخ البداية</label>
                            <input type="date" id="start_date" name="start_date">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">تاريخ النهاية</label>
                            <input type="date" id="end_date" name="end_date">
                        </div>
                    </div>
                </div>

                <!-- الطرف الأول -->
                <div class="form-section">
                    <h3 class="section-title">
                        <span class="section-icon"></span>
                        بيانات الطرف الأول
                    </h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_party_name">اسم الطرف الأول</label>
                            <input type="text" id="first_party_name" name="first_party_name" 
                                   value="شركة سما البنيان التجارية">
                        </div>
                        
                        <div class="form-group">
                            <label for="first_party_commercial_reg">السجل التجاري</label>
                            <input type="text" id="first_party_commercial_reg" name="first_party_commercial_reg" 
                                   value="4030533070">
                        </div>
                        
                        <div class="form-group">
                            <label for="first_party_city">المحافظة</label>
                            <input type="text" id="first_party_city" name="first_party_city" 
                                   value="جدة">
                        </div>
                        
                        <div class="form-group">
                            <label for="first_party_district">الحي</label>
                            <input type="text" id="first_party_district" name="first_party_district" 
                                   value="الحمدانية">
                        </div>
                        
                        <div class="form-group">
                            <label for="first_party_representative">الممثل القانوني</label>
                            <input type="text" id="first_party_representative" name="first_party_representative" 
                                   value="احمد عبدالله سعيد الزهراني">
                        </div>
                    </div>
                </div>

                <!-- الطرف الثاني -->
                <div class="form-section">
                    <h3 class="section-title">
                        <span class="section-icon"></span>
                        بيانات الطرف الثاني
                    </h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="second_party_name">اسم الطرف الثاني <span class="required">*</span></label>
                            <input type="text" id="second_party_name" name="second_party_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="second_party_id">رقم الهوية</label>
                            <input type="text" id="second_party_id" name="second_party_id">
                        </div>
                        
                        <div class="form-group">
                            <label for="second_party_mobile">رقم الجوال</label>
                            <input type="text" id="second_party_mobile" name="second_party_mobile">
                        </div>
                        
                        <div class="form-group">
                            <label for="second_party_email">البريد الإلكتروني</label>
                            <input type="email" id="second_party_email" name="second_party_email">
                        </div>
                    </div>
                </div>

                <!-- التفاصيل المالية -->
                <div class="form-section">
                    <h3 class="section-title">
                        <span class="section-icon"></span>
                        التفاصيل المالية والشروط
                    </h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="capital_amount">مبلغ رأس المال (ريال سعودي) <span class="required">*</span></label>
                            <input type="number" id="capital_amount" name="capital_amount" min="0" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="profit_percentage">نسبة الأرباح (%)</label>
                            <input type="number" id="profit_percentage" name="profit_percentage" 
                                   min="0" max="100" step="0.1" value="30">
                        </div>
                        
                        <div class="form-group">
                            <label for="profit_period_months">دورية تسليم الأرباح (بالأشهر)</label>
                            <input type="number" id="profit_period_months" name="profit_period_months" 
                                   min="1" max="12" value="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="withdrawal_notice_days">مدة الإخطار للانسحاب (بالأيام)</label>
                            <input type="number" id="withdrawal_notice_days" name="withdrawal_notice_days" 
                                   min="1" value="60">
                        </div>
                        
                        <div class="form-group">
                            <label for="penalty_amount">قيمة الشرط الجزائي (ريال سعودي)</label>
                            <input type="number" id="penalty_amount" name="penalty_amount" 
                                   min="0" step="0.01" value="3000">
                        </div>
                        
                        <div class="form-group">
                            <label for="penalty_period_days">مدة الشرط الجزائي (بالأيام)</label>
                            <input type="number" id="penalty_period_days" name="penalty_period_days" 
                                   min="1" value="30">
                        </div>
                        
                        <div class="form-group">
                            <label for="commission_percentage">نسبة العمولة (%)</label>
                            <input type="number" id="commission_percentage" name="commission_percentage" 
                                   min="0" max="100" step="0.1" value="2.5">
                        </div>
                        
                        <div class="form-group">
                            <label for="force_majeure_days">مدة القوة القاهرة (بالأيام)</label>
                            <input type="number" id="force_majeure_days" name="force_majeure_days" 
                                   min="1" value="90">
                        </div>
                    </div>
                </div>

                <!-- البنود والشروط الإضافية -->
                <div class="form-section">
                    <h3 class="section-title">
                        <span class="section-icon"></span>
                        البنود والشروط الإضافية
                    </h3>
                    
                    <div class="form-group">
                        <label for="description">وصف العقد والغرض منه</label>
                        <textarea id="description" name="description" 
                                  placeholder="أدخل وصفاً تفصيلياً للعقد والغرض منه"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="terms_conditions">الشروط والأحكام الإضافية</label>
                        <textarea id="terms_conditions" name="terms_conditions" 
                                  placeholder="أدخل أي شروط أو أحكام إضافية خاصة بهذا العقد"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="contract_clauses">بنود خاصة بالعقد</label>
                        <textarea id="contract_clauses" name="contract_clauses" 
                                  placeholder="أدخل أي بنود خاصة أو استثناءات"></textarea>
                    </div>
                </div>

                <!-- معاينة العقد -->
                <div class="form-section">
                    <h3 class="section-title">
                        <span class="section-icon"></span>
                        معاينة العقد
                    </h3>
                    
                    <button type="button" class="btn-generate-preview" onclick="generatePreview()">
                        إنشاء معاينة للعقد
                    </button>
                    
                    <div id="template-preview" class="template-preview" style="display: none;">
                        <!-- سيتم ملء المعاينة هنا -->
                    </div>
                </div>

                <!-- أزرار الإرسال -->
                <div class="submit-section">
                    <button type="submit" class="btn-submit">إنشاء العقد المفصل</button>
                    <a href="/contracts" class="btn-cancel">إلغاء</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // إنشاء معاينة للعقد
        function generatePreview() {
            const formData = new FormData(document.getElementById('detailedContractForm'));
            const previewDiv = document.getElementById('template-preview');
            
            // جمع البيانات من النموذج
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            // إنشاء معاينة بسيطة
            let preview = `
                <div style="text-align: center; margin-bottom: 20px;">
                    <h3>بسم الله الرحمن الرحيم</h3>
                    <p><strong>رقم العقد:</strong> [سيتم إنشاؤه تلقائياً]</p>
                    <p><strong>نوع العقد:</strong> ${getContractTypeName(data.contract_type)}</p>
                </div>
                
                <p><strong>تم الاتفاق بتاريخ:</strong> ${data.hijri_date || '[يُحدد لاحقاً]'} 
                   <strong>في:</strong> ${data.location || '[يُحدد لاحقاً]'}</p>
                
                <div style="margin: 20px 0;">
                    <p><strong>الطرف الأول:</strong> ${data.first_party_name}</p>
                    <p><strong>السجل التجاري:</strong> ${data.first_party_commercial_reg}</p>
                    <p><strong>المحافظة:</strong> ${data.first_party_city} - <strong>الحي:</strong> ${data.first_party_district}</p>
                    <p><strong>الممثل القانوني:</strong> ${data.first_party_representative}</p>
                </div>
                
                <div style="margin: 20px 0;">
                    <p><strong>الطرف الثاني:</strong> ${data.second_party_name || '[يُملأ لاحقاً]'}</p>
                    <p><strong>رقم الهوية:</strong> ${data.second_party_id || '[يُملأ لاحقاً]'}</p>
                    <p><strong>رقم الجوال:</strong> ${data.second_party_mobile || '[يُملأ لاحقاً]'}</p>
                </div>
                
                <div style="background: rgba(44, 85, 48, 0.05); padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <p><strong>التفاصيل المالية:</strong></p>
                    <p>• مبلغ رأس المال: ${Number(data.capital_amount || 0).toLocaleString()} ريال سعودي</p>
                    <p>• نسبة الأرباح: ${data.profit_percentage || '0'}%</p>
                    <p>• دورية التسليم: كل ${data.profit_period_months || '6'} أشهر</p>
                    <p>• مدة الإخطار للانسحاب: ${data.withdrawal_notice_days || '60'} يوماً</p>
                    <p>• الشرط الجزائي: ${Number(data.penalty_amount || 0).toLocaleString()} ريال لكل ${data.penalty_period_days || '30'} يوم</p>
                </div>
                
                ${data.description ? `<p><strong>وصف العقد:</strong> ${data.description}</p>` : ''}
                
                <p style="text-align: center; margin-top: 30px;">
                    <em>هذه معاينة مبسطة للعقد. النص الكامل سيتم إنشاؤه عند الحفظ.</em>
                </p>
            `;
            
            previewDiv.innerHTML = preview;
            previewDiv.style.display = 'block';
        }
        
        function getContractTypeName(type) {
            switch(type) {
                case 'real_estate_speculation': return 'عقد مضاربة عقارية';
                case 'partnership': return 'عقد شراكة';
                case 'investment': return 'عقد استثمار';
                default: return 'عقد عام';
            }
        }
        
        // تحديث المعاينة عند تغيير نوع العقد
        document.querySelectorAll('input[name="contract_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const previewDiv = document.getElementById('template-preview');
                if (previewDiv.style.display !== 'none') {
                    generatePreview();
                }
            });
        });
        
        // التحقق من صحة النموذج قبل الإرسال
        document.getElementById('detailedContractForm').addEventListener('submit', function(e) {
            const requiredFields = ['title', 'second_party_name', 'capital_amount'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    input.style.borderColor = '#e74c3c';
                    isValid = false;
                } else {
                    input.style.borderColor = 'rgba(44, 85, 48, 0.2)';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('يرجى ملء جميع الحقول المطلوبة');
            }
        });
    </script>
</body>
</html>