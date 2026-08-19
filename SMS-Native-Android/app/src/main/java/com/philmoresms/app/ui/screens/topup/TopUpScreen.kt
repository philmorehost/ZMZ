package com.philmoresms.app.ui.screens.topup

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AccountBalance
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import com.philmoresms.app.network.ErrorUtils
import com.philmoresms.app.network.PaymentSettingsResponse
import com.philmoresms.app.network.RetrofitClient
import com.philmoresms.app.ui.components.AppTopBar
import com.philmoresms.app.ui.components.FintechButton
import com.philmoresms.app.ui.components.FintechInput
import com.philmoresms.app.ui.components.LoadingBox
import com.philmoresms.app.ui.theme.*
import kotlinx.coroutines.launch
import retrofit2.HttpException
import retrofit2.await
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

class TopUpViewModel : ViewModel() {
    var settings by mutableStateOf<PaymentSettingsResponse?>(null)
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var success by mutableStateOf<String?>(null)

    var amount by mutableStateOf("")
    var reference by mutableStateOf("")
    var date by mutableStateOf(SimpleDateFormat("yyyy-MM-dd", Locale.US).format(Date()))

    fun loadSettings() {
        loading = true
        error = null
        viewModelScope.launch {
            try {
                settings = RetrofitClient.apiService.getPaymentSettings().await()
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to load payment settings"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                loading = false
            }
        }
    }

    fun submit() {
        error = null
        success = null
        viewModelScope.launch {
            try {
                val res = RetrofitClient.apiService.submitManualPayment(amount, reference, date).await()
                success = res.message ?: "Payment proof submitted"
                amount = ""
                reference = ""
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to submit payment"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            }
        }
    }
}

@Composable
fun TopUpScreen(navController: NavController) {
    val viewModel: TopUpViewModel = viewModel()
    LaunchedEffect(Unit) { viewModel.loadSettings() }

    Column(modifier = Modifier.fillMaxSize().background(Background)) {
        AppTopBar("Top Up Wallet", onBack = { navController.popBackStack() })

        if (viewModel.loading) {
            LoadingBox()
        } else {
            val mp = viewModel.settings?.manualPayment
            Column(
                modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp)
            ) {
                // Bank details card
                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    color = Color.White,
                    shadowElevation = 1.dp
                ) {
                    Column(Modifier.padding(16.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.AccountBalance, contentDescription = null, tint = Primary)
                            Spacer(Modifier.width(8.dp))
                            Text("Bank Transfer Details", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = TextPrimary)
                        }
                        Spacer(Modifier.height(12.dp))
                        InfoRow("Bank", mp?.bankName ?: "Not set")
                        InfoRow("Account Name", mp?.accountName ?: "Not set")
                        InfoRow("Account Number", mp?.accountNumber ?: "Not set")
                        if (!mp?.instructions.isNullOrBlank()) {
                            Text(mp?.instructions ?: "", fontSize = 13.sp, color = TextSecondary, modifier = Modifier.padding(top = 12.dp))
                        }
                    }
                }

                if (viewModel.error != null) Text(viewModel.error ?: "", color = Danger, fontSize = 13.sp, modifier = Modifier.padding(top = 16.dp))
                if (viewModel.success != null) Text(viewModel.success ?: "", color = Success, fontSize = 13.sp, modifier = Modifier.padding(top = 16.dp))

                Text("Submit Payment Proof", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = TextPrimary, modifier = Modifier.padding(top = 24.dp, bottom = 16.dp))
                FintechInput("Amount (₦)", viewModel.amount, { viewModel.amount = it })
                FintechInput("Transaction Reference", viewModel.reference, { viewModel.reference = it })
                FintechInput("Payment Date", viewModel.date, { viewModel.date = it })
                FintechButton("Submit Payment Proof", { viewModel.submit() })
                Spacer(Modifier.height(24.dp))
            }
        }
    }
}

@Composable
private fun InfoRow(label: String, value: String) {
    Row(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
        Text(label, fontSize = 13.sp, color = TextSecondary, modifier = Modifier.width(130.dp))
        Text(value, fontSize = 14.sp, fontWeight = FontWeight.SemiBold, color = TextPrimary)
    }
}
